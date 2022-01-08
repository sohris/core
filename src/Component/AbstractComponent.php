<?php
namespace Sohris\Core\Component;

abstract class AbstractComponent implements IComponent
{
    private $name;

    public $priority = 0;
    
    public function getName(){
        return $this->name;
    }
}