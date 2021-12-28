<?php
namespace Sohris\Core;

use React\EventLoop\LoopInterface;

abstract class AbstractComponent
{
    private $name;

    public $priority = 0;
    
    public function getName(){
        return $this->name;
    }

    public function install()
    {

    }

    public function start()
    {
        
    }

}