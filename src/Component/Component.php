<?php

namespace Sohris\Core\Component;

class Component
{
    private AbstractComponent $component;

    public function __construct(string $component)
    {
        $this->component = new $component();
    }


    public function start()
    {
        $this->component->start();
    }

    public function install()
    {
        $this->component->install();
    }
}
