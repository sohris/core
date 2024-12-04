<?php

namespace Sohris\Core\Tools\Worker;

use Cron\CronExpression;
use DateTime;
use Exception;
use parallel\Channel;
use parallel\Runtime;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
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
    private $stage = 'unloaded';
    private $err_code = 0;
    private $err_time = 0;
    private $err_msg = '';
    private $err_file = '';
    private $err_line = '';
    private $err_trace = [];
    private static $logger;

    private static $stopped_id;
    private static $killed_id;


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
                    $this->err_trace = array_map(fn($e2) => ["file" => $e2['file'], "line" => $e2['line']], array_slice($e->getTrace(), 0, 5));
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

    public function run()
    {
        if ($this->stage == 'stopped') {
            ChannelController::send($this->channel_name . "_controller", 'start');
            $this->stage = 'running';
            return;
        }
        $configs = Utils::getConfigFiles("system");

        if (isset($configs['bootstrap_file']))
            $bootstrap = $configs['bootstrap_file'];
        else
            $bootstrap = Server::getRootDir() . DIRECTORY_SEPARATOR . "bootstrap.php";
        if (!is_file($bootstrap)) {
            self::$logger->info("Can't open bootstrap file ($bootstrap)");
            $bootstrap = null;
        }
        $this->runtime = new Runtime($bootstrap);
        $this->task = $this->runtime->run(static function (
            $callbacks_on_first,
            $callbacks,
            $callbacks_crontab,
            $callbacks_timeout,
            $verbose,
            $root_dir,
            $channel_name
        ) {
            $task = new Task(
                $callbacks_on_first,
                $callbacks,
                $callbacks_crontab,
                $callbacks_timeout,
                $verbose,
                $root_dir,
                $channel_name
            );
            $task->run();
        }, [
            $this->callbacks_on_first,
            $this->callbacks,
            $this->callbacks_crontab,
            $this->callbacks_timeout,
            Server::getOutput()->getVerbosity(),
            Server::getRootDir(),
            $this->channel_name
        ]);
        $this->stage = 'running';
    }

    public function kill($mode = "kill"): PromiseInterface
    {
        $def = new Deferred();
        // if ($this->stage != 'running' && $this->stage != 'stopped')
        if (isset(self::$killed_id))
            ChannelController::disable($this->channel_name . "_controller", "killed", self::$killed_id);
        self::$killed_id = ChannelController::on($this->channel_name, "killed", function () use ($def) {
            $this->stage = 'unloaded';
            $this->runtime->close();
            unset($this->runtime);
            $def->resolve(true);
        });
        ChannelController::send($this->channel_name . "_controller", 'kill', ["by" => "$mode()", "backtrace" => debug_backtrace(2, 10)]);
        return $def->promise();
    }

    public function stop(): PromiseInterface
    {
        $def = new Deferred();

        if (isset(self::$stopped_id))
            ChannelController::disable($this->channel_name . "_controller", "stopped", self::$stopped_id);
        self::$stopped_id = ChannelController::on($this->channel_name . "_controller", "stopped", function () use ($def) {
            $this->stage = 'stopped';
            $def->resolve(true);
        });
        ChannelController::send($this->channel_name . "_controller", 'stop');
        return $def->promise();
    }

    public function restart()
    {
        return $this->kill("restart")->then(fn() => $this->run());
    }

    public function getStage()
    {
        return $this->stage;
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
