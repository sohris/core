<?php

namespace Sohris\Core\Components\ConfigLoader;

use React\EventLoop\LoopInterface;
use Sohris\Core\AbstractComponent;
use Sohris\Core\Server;

class Config extends AbstractComponent
{

    public $config_file = "";

    public function __construct(LoopInterface $loop)
    {
        $this->server = Server::getServer();
        $this->server->on("beforeStart",fn() => $this->configureConfigs());

    }

    private function configureConfigs()
    {
        
    }




}