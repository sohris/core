<?php

namespace Sohris\Core;

use Evenement\EventEmitter;
use Sohris\Core\Exceptions\ServerException;

class Server
{

    const EVENTS_ENABLED = array("beforeStart","start", "running", "error", "stop", "pause");
    const FILE_SYSTEM_MONITOR = "system_monitor";

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Evenement\EventEmitter
     */
    private $events;

    private $components = array();

    private $sys_log_file;

    private static $server;

    public static function getServer(): Server
    {
        if (is_null(self::$server)) {
            return new Server;
        }

        return self::$server;
    }

    public function __construct()
    {

        self::$server = $this;

        $this->loop = Loop::getLoop();

        $this->events = new EventEmitter;

        Loader::loadClasses();

        $this->configSystemMonitor();
        $this->loadComponents();

        $this->events->emit("beforeStart");
    }

    private function configSystemMonitor()
    {
        $file_path = __DIR__ . DIRECTORY_SEPARATOR . self::FILE_SYSTEM_MONITOR;

        $this->loop->addPeriodicTimer(
            5,
            function () use ($file_path) {

                $file = fopen($file_path, "w+");

                $data = array(
                    "real_memory" => Utils::bytesToHuman(memory_get_usage(false)),
                    "total_memory" => Utils::bytesToHuman(memory_get_usage(true)),
                    "pid" => getmypid(),
                    "loadavg" => 0,
                    "last_update" => date('Y-m-d H:i:s')
                );

                fwrite($file, json_encode($data));
                fclose($file);
            }
        );
    }

    private function loadComponents()
    {
        $classes = Loader::getComponentsClass();

        foreach ($classes as $class) {
            $component = new Component($class);
            $this->components[] = $component;
        }
    }

    public function run()
    {
        $this->events->emit("running");
        $this->loop->run();
    }

    public function on(string $event, callable $func)
    {

        if (is_null($func)) {
            throw new ServerException("Callable can not be NULL!");
        }

        if (!in_array($event, self::EVENTS_ENABLED)) {
            throw new ServerException("Event $event is not register!");
        }

        $this->events->on($event, $func);
    }
}
