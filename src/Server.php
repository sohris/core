<?php

namespace Sohris\Core;

use Evenement\EventEmitter;
use Exception;
use Sohris\Core\Components\Logger;
use Sohris\Core\Exceptions\ServerException;
use Throwable;

class Server
{

    const EVENTS_ENABLED = array("server.beforeStart", "server.start", "server.running", "server.error", "server.stop", "server.pause", "components.loaded", "components.installed", "components.started", "components.register");
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

    private $root_dir = '';

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

        //$this->configSystemMonitor();
        $this->loadComponents();

        $this->events->emit("server.beforeStart");
    }

    public function setRootDir(string $dir)
    {
        $this->root_dir = realpath($dir);
    }

    private function configSystemMonitor()
    {
        $file_path = $this->root_dir . DIRECTORY_SEPARATOR . self::FILE_SYSTEM_MONITOR;

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
            $this->register_components[] = $class;
        }

        sort($this->register_components);

        $this->events->emit('components.register');

        foreach ($this->register_components as $key => $component) {
            $this->components[$key] = new Component($component);
        }
        $this->events->emit('components.loaded');
    }

    private function installComponents()
    {
        try {

            foreach ($this->components as $component) {
                $component->install();
            }
        } catch (Throwable $e) {
            Logger::critical($e->getMessage());
        }

        Logger::debug(sizeof($this->components) . " components Installed");
        $this->events->emit("components.installed");
    }


    private function startComponents()
    {
        foreach ($this->components as $component) {
            $component->start();
        }

        $this->events->emit("components.started");
    }

    public function run()
    {

        $this->installComponents();
        $this->startComponents();
        $this->events->emit("server.running");
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
