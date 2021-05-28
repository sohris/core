<?php

namespace Sohris\Core;

use Sohris\Core\Interfaces\ModuleInterface;
class Module
{

    private $module;

    private $active = false;

    public function __construct(string $module, Server $server)
    {
        $this->module = new $module($server);   
    }


    
}