<?php
namespace Sohris\Core\Interfaces;

use React\EventLoop\LoopInterface;

abstract class AbstractComponent
{
    private $name;
    
    public abstract function __construct(LoopInterface $loop);

    public function getName(){
        return $this->name;
    }
}