<?php

namespace Sohris\Core\Tools\Worker;

use Cron\CronExpression;
use DateTime;
use Exception;
use parallel\Runtime;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Sohris\Core\Logger;
use Sohris\Core\Server;
use Sohris\Core\Utils;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class Worker
{
    private $runtime;
    private $channel_name;
    private static $id = 0;
    private $callbacks = [];
    private $callbacks_on_first = [];
    private $callbacks_crontab = [];
    private $callbacks_timeout = [];
    private $task;
    private $stay_alive = false;
    private static $first = false;
    private $stage = 'unloaded';
    private $err_code = 0;
    private $err_time = 0;
    private $err_msg = '';
    private $err_file = '';
    private $err_line = '';
    private $err_trace = [];
    private static $timers = [];
    private static $cron_timers = [];
    private $last_interaction = 0;
    private static $logger;

    /**
     * @var LoopInterface
     */
    private static $loop;

    public function __construct()
    {
        self::$id++;
        self::$logger = new Logger("CoreWorker");
        $this->channel_name = sha1(self::$id . time());
        $this->stage = 'loaded';
        ChannelController::on($this->channel_name, 'error', function ($err_info) {
            $this->stage = 'error';
            $this->err_time = time();
            $this->err_code = $err_info['errcode'];
            $this->err_msg = $err_info['errmsg'];
            $this->err_trace = $err_info['trace'];
        });
        Loop::addPeriodicTimer(5, function () {
            if (!$this->task) return;
            if ($this->task->done() && $this->stay_alive) {
                try {
                    $this->task->value();
                } catch (Throwable $e) {
                    $this->err_time = time();
                    $this->err_code = $e->getCode();
                    $this->err_file = $e->getFile();
                    $this->err_line = $e->getLine();
                    $this->err_msg = $e->getMessage();
                    $this->err_trace = array_map(fn ($e2) => ["file" => $e2['file'], "line" => $e2['line']], array_slice($e->getTrace(), 0, 5));
                }
                $error = $this->getLastError();
                unset($error['trace']);
                ChannelController::send($this->channel_name, 'restart', $error);
                $this->stage = "death";
                $this->restart();
            }
        });
    }

    public function __destruct()
    {
        if ($this->runtime)
            $this->runtime->kill();
        $this->stage = 'unloaded';
    }

    public function callOnFirst(callable $callback)
    {
        $this->callbacks_on_first[] = $callback;
    }

    public function callFunction(callable $callback, float $timer)
    {
        $this->callbacks[] = [
            "timer" => $timer,
            "callable" => $callback
        ];
    }

    public function callCronFunction(callable $callback, string $crontab)
    {

        $this->callbacks_crontab[] = [
            "crontab" => $crontab,
            "callable" =>  $callback
        ];
    }

    public function callTimeoutFunction(callable $callback, string $timeout)
    {

        $this->callbacks_timeout[] = [
            "timeout" => $timeout,
            "callable" =>  $callback
        ];
    }
    public function stayAlive()
    {
        $this->stay_alive = true;
    }

    public function on(string $event_name, callable $callback)
    {
        ChannelController::on($this->channel_name, $event_name, $callback);
    }

    public function stop()
    {
        ChannelController::send($this->channel_name . "_controller", 'stop');
        $this->stage = 'stopped';
    }

    public function restart()
    {
        if ($this->stage == 'running')
            ChannelController::send($this->channel_name . "_controller", 'kill');
        $this->stage = 'restarted';
        $this->run();
    }

    public function run()
    {
        if ($this->stage == 'stopped') {
            ChannelController::send($this->channel_name . "_controller", 'start');
            $this->stage = 'running';
            return;
        }
        $channel_name = $this->channel_name;
        $configs = Utils::getConfigFiles("system");

        if (isset($configs['bootstrap_file']))
            $bootstrap = $configs['bootstrap_file'];
        else
            $bootstrap = Server::getRootDir() . DIRECTORY_SEPARATOR . "bootstrap.php";
        if(!is_file($bootstrap)){
            self::$logger->info("Can't open bootstrap file ($bootstrap)");
            $bootstrap = null;
        }
        $this->runtime = new Runtime($bootstrap);
        $output = Server::getOutput();
        $params_output = [
            "verbose" => $output->getVerbosity(),
            "formatter" => $output->getFormatter()
        ];
        $this->task = $this->runtime->run(static function ($on_first, $tasks, $tasks_crontab, $tasks_timeout, $params_output) use ($channel_name) {
            self::$logger = new Logger("RuntimeWorker");
            try {
                $server = Server::getServer();
                $server->loadServer();
                $server->setOutput(new ConsoleOutput($params_output['verbose'],null, $params_output['formatter']));
                $createTimers = function () use ($tasks, $tasks_crontab, $tasks_timeout, $channel_name) {
                    foreach ($tasks as $calls) {
                        if (!array_key_exists('timer', $calls)) continue;
                        self::$timers[] = self::$loop->addPeriodicTimer(
                            $calls['timer'],
                            fn () => $calls['callable'](
                                fn ($event_name, $args) => ChannelController::send($channel_name, $event_name, $args)
                            )
                        );
                    }
                    foreach ($tasks_timeout as $calls) {
                        if (!array_key_exists('timeout', $calls)) continue;
                        self::$timers[] = self::$loop->addTimer(
                            $calls['timeout'],
                            fn () => $calls['callable'](
                                fn ($event_name, $args) => ChannelController::send($channel_name, $event_name, $args)
                            )
                        );
                    }
                    foreach ($tasks_crontab as $key => $cron_calls) {
                        self::reconfigureCronTimer($key, $cron_calls, $channel_name);
                    }
                };
                ChannelController::on($channel_name . "_controller", 'stop', function () {
                    self::$logger->debug("Stopping");
                    array_walk(self::$timers, fn ($timer) => self::$loop->cancelTimer($timer));
                    array_walk(self::$cron_timers, fn ($timer) => self::$loop->cancelTimer($timer));
                    self::$timers = [];
                });
                ChannelController::on($channel_name . "_controller", 'start', function () use ($createTimers) {

                    self::$logger->debug("Starting");
                    $createTimers();
                });
                ChannelController::on($channel_name . "_controller", 'kill', function () {

                    self::$logger->debug("Killing");
                    exit;
                });
                if (!self::$first) {
                    self::$first = true;
                    self::$loop = Loop::get();
                    if (!empty($on_first))
                        array_walk($on_first, fn ($el) => $el(fn ($event_name, $args) => ChannelController::send($channel_name, $event_name, $args)));
                }
                $createTimers();
                self::$loop->run();
            } catch (Exception $e) {
                self::$logger->exception($e);
                ChannelController::send($channel_name, 'error', ['errmsg' => $e->getMessage(), 'errcode' => $e->getCode(), 'errfile' => $e->getFile(), 'errline' => $e->getLine(), 'trace' => $e->getTrace()]);
            } catch (Throwable $e) {
                self::$logger->throwable($e);
                ChannelController::send($channel_name, 'error', ['errmsg' => $e->getMessage(), 'errcode' => $e->getCode(), 'errfile' => $e->getFile(), 'errline' => $e->getLine(), 'trace' => $e->getTrace()]);
            }
        }, [$this->callbacks_on_first, $this->callbacks, $this->callbacks_crontab, $this->callbacks_timeout, $params_output]);
        $this->stage = 'running';
    }

    public function kill()
    {
        if ($this->stage != 'running' && $this->stage != 'stopped') return;
        $this->stage = 'unloaded';
        ChannelController::send($this->channel_name . "_controller", 'kill');
        $this->runtime->close();
        unset($this->runtime);
    }

    public function getStage()
    {
        return $this->stage;
    }

    private static function reconfigureCronTimer($key, $task, $channel_name)
    {
        if (array_key_exists($key, self::$cron_timers) && self::$cron_timers instanceof TimerInterface) {
            self::$loop->cancelTimer(self::$cron_timers[$key]);
        }

        $now = new DateTime();

        $cron = str_replace("\\", "/", $task['crontab']);
        $time = CronExpression::factory($cron);
        $to_run = $time->getNextRunDate();

        $diff = $to_run->getTimestamp() - $now->getTimestamp();
        self::$cron_timers[$key] = self::$loop->addTimer($diff, function () use ($key, $task, $channel_name) {
            \call_user_func(
                $task['callable'],
                fn ($event_name, $args) => ChannelController::send($channel_name, $event_name, $args)
            );
            self::reconfigureCronTimer($key, $task, $channel_name);
        });
    }

    public function getLastError()
    {
        return [
            'timestamp' => $this->err_time,
            'message' => $this->err_msg,
            'code' => $this->err_code,
            'line' => $this->err_line,
            'file' => $this->err_file,
            'trace' => is_array($this->err_trace) ? array_slice($this->err_trace, 0, 3) : $this->err_trace
        ];
    }

    public function getChannelName()
    {
        return $this->channel_name;
    }

    public function clearTimeoutCallFunction()
    {
        $this->callbacks_timeout = [];
    }

    public function clearCallFunction()
    {
        $this->callbacks = [];
    }

    public function clearCallCronFunction()
    {
        $this->callbacks_crontab = [];
    }

    public function clearCallOnFirstFunction()
    {
        $this->callbacks_on_first = [];
    }
}
