<?php

namespace Sohris\Core;

use Evenement\EventEmitter;
use Exception;
use React\EventLoop\Loop;
use Sohris\Core\Component\Component;
use Sohris\Core\Exceptions\ServerException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

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

    private static Logger $logger;

    private static $server;

    private static $root_dir = './';

    private static $output;

    private static $verbose = ConsoleOutput::VERBOSITY_NORMAL;

    public function __construct(OutputInterface $output = new ConsoleOutput())
    {
        self::$server = $this;
        if (!is_null($output))
            self::$output = $output;

        self::$logger = new Logger();
        $this->loop = Loop::get();
        $this->events = new EventEmitter;
        $this->start = time();
        $this->events->emit("server.beforeStart");
    }

    private function loadComponents()
    {
        $this->logger->debug("Loading Components");
        $classes = Loader::getClassesWithParent(self::COMPONENT_NAME);
        foreach ($classes as $class) {
            $this->logger->debug("Loaging Component $class");
            $this->components[sha1($class)] = new $class;
        }
        $this->events->emit('components.loaded');
        $this->logger->info('Components Loaded [' . count($classes) . ']');
    }

    private function executeInstallInAllComponents()
    {
        try {
            $this->logger->debug("Install Components");
            foreach ($this->components as $component) {
                $this->logger->debug("Install Component " . get_class($component));
                $component->install();
            }
        } catch (\Throwable $e) {
            $this->logger->throwable($e);
        }
        $this->logger->info("Components Installed");
    }

    public function run()
    {
        $this->loadServer();
        $this->executeInstallInAllComponents();
        $this->events->emit("components.installed");

        $this->executeStartInAllComponents();
        $this->events->emit("components.started");

        $this->events->emit("server.running");
        $this->loop->run();
    }

    public function loadServer()
    {
        Loader::loadClasses();
        $this->loadComponents();
    }

    private function executeStartInAllComponents()
    {
        try {

            $this->logger->debug("Starting Components");
            foreach ($this->components as $component) {
                $this->logger->debug("Start Component " . get_class($component));
                $component->start();
            }
        } catch (\Throwable $e) {
            $this->logger->throwable($e);
        }
        $this->logger->info("Components Installed");
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
        if (!is_dir($path))
            throw new ServerException("Path \"$path\" is not a dir");
        self::$root_dir = $path;
    }

    public function getComponent(string $component_name): ComponentControl
    {
        $key = sha1($component_name);
        if (!array_key_exists($key, $this->components))
            throw new Exception("Component not register $component_name");
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

    public static function getOutput()
    {
        if (!isset(self::$output))
            return new ConsoleOutput(self::$verbose);
        return self::$output;
    }

    public static function setOutput(OutputInterface $output)
    {
        self::$output = $output;
        self::$logger = new Logger();
    }
}
