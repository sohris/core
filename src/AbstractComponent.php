<?php
namespace Sohris\Core;

use React\EventLoop\LoopInterface;

abstract class AbstractComponent
{
    private $name;

    public $priority = 0;
    
    public abstract function __construct(LoopInterface $loop);

    public function getName(){
        return $this->name;
    }
}