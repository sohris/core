<?php
namespace Sohris\Core\Component;

abstract class AbstractComponent implements IComponent
{
    protected $name = "AbstractComponent";

    public $priority = 0;
    
    public function getName(){
        return $this->name;
    }
}