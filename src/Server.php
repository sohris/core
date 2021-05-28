<?php

namespace Sohris\Core;

use Evenement\EventEmitter;
use Exception;

class Server
{

    const EVENTS_ENABLED = array("server.start", "server.running", "server.error", "server.stop");
    const FILE_SYSTEM_MONITOR = "system_monitor";
    const MODULE_INTERFACE = "Sohris\Core\Interfaces\ModuleInterface";

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Evenement\EventEmitter
     */
    private $events;

    private $modules = array();

    private $sys_log_file;

    public function __construct()
    {
        $this->loop = Loop::getLoop();

        $this->events = new EventEmitter;

        Utils::loadLocalClasses();
        Utils::loadVendorClasses();

        $this->configSystemMonitor();
        $this->loadModules();
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

    private function loadModules()
    {
        $classes = get_declared_classes();

        foreach ($classes as $class) {
            if (in_array(self::MODULE_INTERFACE, class_implements($class))) {
                $this->modules[] = new Module($class, $this);
            }
        }

    }

    public function run()
    {
        $this->events->emit("server.running");
        $this->loop->run();
    }

    public function on(string $event, callable $func)
    {

        if (is_null($func)) {
            throw new Exception("Callable can not be NULL!");
        }

        if (!in_array($event, self::EVENTS_ENABLED)) {
            throw new Exception("Event $event is not register!");
        }

        $this->events->on($event, function () use ($func) {
            
            $func;
        });
    }
}
