<?php

namespace Sohris\Core;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use Sohris\Core\Component\Component;
use Sohris\Core\Exceptions\ServerException;

class Server
{

    const EVENTS_ENABLED = array("server.beforeStart", "server.start", "server.running", "server.error", "server.stop", "server.pause", "components.loaded", "components.installed", "components.started", "components.register");
    const FILE_SYSTEM_MONITOR = "system_monitor";
    const COMPONENT_NAME = "Sohris\Core\ComponentControl";


    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Evenement\EventEmitter
     */
    private $events;

    private $start = 0;

    private $components = array();

    private $logger;

    private static $server;

    private static $root_dir = './';

    public function __construct()
    {
        self::$server = $this;
        $this->loop = Loop::get();
        $this->events = new EventEmitter;
        $this->start = time();
        $this->events->emit("server.beforeStart");

        Loader::loadClasses();
    }

    private function loadComponents()
    {
        $classes = Loader::getClassesWithParent(self::COMPONENT_NAME);
        foreach ($classes as $class) {            
            $this->components[sha1($class)] = new $class;
        }
        $this->events->emit('components.loaded');
    }

    private function executeInstallInAllComponents()
    {
        try {
            foreach ($this->components as $component) {
                $component->install();
            }
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage());
        }
    }


    public function run()
    {
        
        $this->loadComponents();
        $this->logger = new Logger();
        $this->executeInstallInAllComponents();
        $this->events->emit("components.installed");

        $this->executeStartInAllComponents();
        $this->events->emit("components.started");

        $this->events->emit("server.running");
        $this->loop->run();
    }

    private function executeStartInAllComponents()
    {
        try {
            foreach ($this->components as $component) {
                $component->start();
            }
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage());
        }
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

    public function setRootDir(string $path)
    {
        self::$root_dir = realpath($path);
    }

    public function getComponent(string $component_name) : ComponentControl
    {   
        $key = sha1($component_name);
        if(!array_key_exists($key, $this->components)) return null;
        return $this->components[$key];
    }

    public static function getServer(): Server
    {
        if (is_null(self::$server)) {
            return new Server;
        }

        return self::$server;
    }

    public static function getRootDir()
    {
        return self::$root_dir;
    }
    
    public function getUptime()
    {
        return time() - $this->start;
    }
}
