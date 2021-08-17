<?php

namespace Sohris\Core;

class Component
{

    private $component;

    private $active = false;

    private $loop;

    public function __construct(string $component)
    {
        $this->loop = Loop::getLoop();
        $this->component = new $component($this->loop);   
    }
    
}