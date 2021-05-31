<?php

namespace Sohris\Core;

use Sohris\Core\Interfaces\ModuleInterface;
class Module
{

    private $module;

    private $active = false;

    private $loop;

    public function __construct(string $module)
    {
        $this->loop = Loop::getLoop();
        $this->module = new $module($this->loop);   
    }
    
}