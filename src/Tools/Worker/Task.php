<?php

namespace Sohris\Core\Tools\Worker;

use Cron\CronExpression;
use DateTime;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use Sohris\Core\Logger;
use Sohris\Core\Server;
use Symfony\Component\Console\Output\ConsoleOutput;

class Task
{
    private $tasks_to_on_first_execution = [];
    private $tasks_to_periodic_execution = [];
    private $tasks_to_periodic_cron_execution = [];
    private $tasks_to_timeout_execution = [];

    private $output_verbose = "vvv";
    private $root_dir = __DIR__;
    private $channel_name = '';
    private Logger $logger;
    private $timers = [];
    private $crontab_timers = [];

    public function __construct(
        $tasks_to_on_first_execution = [],
        $tasks_to_periodic_execution = [],
        $tasks_to_periodic_cron_execution = [],
        $tasks_to_timeout_execution = [],
        $output_verbose = "vvv",
        $root_dir = __DIR__,
        $channel_name = ''
    ) {
        $this->tasks_to_on_first_execution = $tasks_to_on_first_execution;
        $this->tasks_to_periodic_execution = $tasks_to_periodic_execution;
        $this->tasks_to_periodic_cron_execution = $tasks_to_periodic_cron_execution;
        $this->tasks_to_timeout_execution = $tasks_to_timeout_execution;
        $this->output_verbose = $output_verbose;
        $this->root_dir = $root_dir;
        $this->channel_name = $channel_name;

        $this->logger = new Logger("RuntimeWorker");
    }



    public function run()
    {
        try {
            $this->configureServer();

            $this->configureEvents();

            $this->configureTimers();

            if (!empty($this->tasks_to_on_first_execution))
                array_walk($this->tasks_to_on_first_execution, fn($el) => $el(fn($event_name, $args) => ChannelController::send($this->channel_name, $event_name, $args)));        

            Loop::run();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            ChannelController::send($this->channel_name, 'error', ['errmsg' => $e->getMessage(), 'errcode' => $e->getCode(), 'errfile' => $e->getFile(), 'errline' => $e->getLine(), 'trace' => $e->getTrace()]);
        } catch (\Throwable $e) {
            $this->logger->throwable($e);
            ChannelController::send($this->channel_name, 'error', ['errmsg' => $e->getMessage(), 'errcode' => $e->getCode(), 'errfile' => $e->getFile(), 'errline' => $e->getLine(), 'trace' => $e->getTrace()]);
        }
    }

    private function configureServer()
    {
        $server = Server::getServer();
        $server::hideStatus();
        $server::setOutput(new ConsoleOutput($this->output_verbose));
        $server->setRootDir($this->root_dir);
        $server->loadServer();
    }

    private function configureTimers()
    {
        foreach ($this->tasks_to_periodic_execution as $calls) {
            if (!array_key_exists('timer', $calls)) continue;
            $this->timers[] = Loop::addPeriodicTimer(
                $calls['timer'],
                fn() => $calls['callable'](
                    fn($event_name, $args) => ChannelController::send($this->channel_name, $event_name, $args)
                )
            );
        }
        foreach ($this->tasks_to_timeout_execution as $calls) {
            if (!array_key_exists('timeout', $calls)) continue;
            $this->timers[] = Loop::addTimer(
                $calls['timeout'],
                fn() => $calls['callable'](
                    fn($event_name, $args) => ChannelController::send($this->channel_name, $event_name, $args)
                )
            );
        }
        foreach ($this->tasks_to_periodic_cron_execution as $key => $cron_calls) {
            self::reconfigureCronTimer($key, $cron_calls, $this->channel_name);
        }
    }

    private function configureEvents()
    {

        ChannelController::on($this->channel_name . "_controller", 'start', function () {
            $this->logger->debug("Starting");
            $this->configureTimers();
            ChannelController::send($this->channel_name, 'started');
        });

        ChannelController::on($this->channel_name . "_controller", 'stop', function () {
            $this->logger->debug("Stopping");
            $this->stopTimers();
            ChannelController::send($this->channel_name, 'stopped');
        });

        ChannelController::on($this->channel_name . "_controller", 'kill', function ($arg) {
            $this->logger->debug("Killing", $arg);
            $this->stopTimers();
            ChannelController::send($this->channel_name, 'killed');
            Loop::stop();
        });
    }

    private function stopTimers()
    {
        array_walk($this->timers, fn($timer) => Loop::cancelTimer($timer));
        array_walk($this->crontab_timers, fn($timer) => Loop::cancelTimer($timer));
        $this->timers = [];
        $this->crontab_timers = [];
    }

    private function reconfigureCronTimer($key, $task, $channel_name)
    {
        if (array_key_exists($key, $this->crontab_timers) && $this->crontab_timers instanceof TimerInterface) {
            Loop::cancelTimer($this->crontab_timers[$key]);
        }

        $now = new DateTime();

        $cron = str_replace("\\", "/", $task['crontab']);
        $time = CronExpression::factory($cron);
        $to_run = $time->getNextRunDate();

        $diff = $to_run->getTimestamp() - $now->getTimestamp();
        $this->crontab_timers[$key] = Loop::addTimer($diff, function () use ($key, $task) {
            \call_user_func(
                $task['callable'],
                fn($event_name, $args) => ChannelController::send($this->channel_name, $event_name, $args)
            );
            self::reconfigureCronTimer($key, $task, $this->channel_name);
        });
    }
}
