<?php

namespace Sohris\Core\Tools\Worker;

use Exception;
use parallel\Channel;
use parallel\Events;
use parallel\Runtime;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Sohris\Core\Server;

class Worker
{
    private $runtime;
    private $channel_name;
    private static $id = 0;
    private $callbacks = [];
    private $callbacks_on_first = [];
    private static $first = false;
    private $stage = 'unloaded';
    private $err_code = 0;
    private $err_msg = '';
    private $err_trace = [];
    private static $timers = [];

    /**
     * @var LoopInterface
     */
    private static $loop;

    public function __construct()
    {
        self::$id++;

        $this->channel_name = sha1(self::$id . time());
        $this->stage = 'loaded';
        ChannelController::on($this->channel_name, 'error', function ($err_info) {
            $this->stage = 'error';
            $this->err_code = $err_info['errcode'];
            $this->err_msg = $err_info['errmsg'];
            $this->err_trace = $err_info['trace'];
        });
    }

    public function __destruct()
    {
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
            "callable" =>  $callback
        ];
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
        if ($this->stage != 'running') return;
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
        $bootstrap = Server::getRootDir() . DIRECTORY_SEPARATOR . "bootstrap.php";
        $this->runtime = new Runtime($bootstrap);
        $this->runtime->run(function ($on_first, $tasks) use ($channel_name) {
            try {
                set_error_handler(function (...$err) use ($channel_name) {
                    ChannelController::send($channel_name, 'error', ['errmsg' => $err[2], 'errcode' => $err[1], 'trace' => $err[3]]);
                });
                if (!self::$first) {
                    self::$first = true;
                    self::$loop = Loop::get();
                    if (!empty($on_first))
                        array_walk($on_first, fn ($el) => $el(fn ($event_name, mixed $args) => ChannelController::send($channel_name, $event_name, $args)));
                }
                if (!empty($tasks)) {
                    $createTimers = function () use ($tasks, $channel_name) {
                        foreach ($tasks as $calls)
                            self::$timers[] = self::$loop->addPeriodicTimer(
                                $calls['timer'],
                                fn () => $calls['callable'](
                                    fn ($event_name, mixed $args) => ChannelController::send($channel_name, $event_name, $args)
                                )
                            );
                    };
                    ChannelController::on($channel_name . "_controller", 'stop', function () {
                        array_walk(self::$timers, fn ($timer) => self::$loop->cancelTimer($timer));
                        self::$timers = [];
                    });
                    ChannelController::on($channel_name . "_controller", 'start', function () use ($createTimers) {
                        $createTimers();
                    });
                    ChannelController::on($channel_name . "_controller", 'kill', function () {
                        exit;
                    });
                    $createTimers();
                    self::$loop->run();
                }
            } catch (Exception $e) {
                ChannelController::send($channel_name, 'error', ['errmsg' => $e->getMessage(), 'errcode' => $e->getCode(), 'trace' => $e->getTrace()]);
            }
        }, [$this->callbacks_on_first, $this->callbacks]);
        $this->stage = 'running';
    }

    public function kill()
    {
        if ($this->stage != 'running' && $this->stage != 'stopped') return;
        $this->stage = 'unloaded';
        ChannelController::send($this->channel_name . "_controller", 'kill');
    }

    public function getStage()
    {
        return $this->stage;
    }



    public function getLastError()
    {
        return [
            'message' => $this->err_msg,
            'code' => $this->err_code,
            'trace' => $this->err_trace
        ];
    }

    public function getChannelName()
    {
        return $this->channel_name;
    }
}
