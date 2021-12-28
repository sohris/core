<?php

namespace Sohris\Core;


use Evenement\EventEmitter;
use Sohris\Core\Exceptions\ComponentException;

class Component
{
    const EVENTS_ENABLED = ["component.started", "component.installed"];

    private AbstractComponent $component;

    private $active = false;

    private $loop;

    public function __construct(string $component)
    {
        $this->loop = Loop::getLoop();
        $this->component = new $component();
        $this->component->getName();
        $this->events = new EventEmitter;
    }


    public function start()
    {
        $this->component->start();
        $this->events->emit("component.started");
    }

    public function install()
    {
        $this->component->install();
        $this->events->emit("component.installed");
    }

    public function on(string $event, callable $func)
    {

        if (is_null($func)) {
            throw new ComponentException("Callable can not be NULL!");
        }

        if (!in_array($event, self::EVENTS_ENABLED)) {
            throw new ComponentException("Event Component $event is not register!");
        }

        $this->events->on($event, $func);
    }
}
