<?php

namespace Sohris\Core;

class Server
{


    /**
     * @var React\EventLoop\LoopInterface
     */
    private $loop;

    private $sys_log_file;
    
    public function __construct()
    {
        $this->loop = Loop::getLoop();

        Utils::loadLocalClasses();
        Utils::loadVendorClasses();

        $this->configSystemMonitor();

    }

    private function configSystemMonitor()
    {


        //$file = __DIR__ . DIRECTORY_SEPARATOR . ·
        //$stream_file = new \React\Stream\WritableResourceStream(fopen());



    }



    public function run()
    {
        $this->loop->run();
    }



}