<?php 

namespace Sohris\Core;

class Loader 
{    
    
    const COMPONENT_INTERFACE = "Sohris\Core\AbstractComponent";

    private static $component_class = array();

    public static function loadClasses()
    {
        Utils::loadLocalClasses();
        Utils::loadVendorClasses();

        $all_classes = get_declared_classes();
        foreach($all_classes as $class)
        {
            $implenets = class_parents($class);
            
            if (in_array(self::COMPONENT_INTERFACE, $implenets)) {

                array_push(self::$component_class, $class);
            }

        }

    }

    public static function getComponentsClass(): array
    {

        return self::$component_class;
    }


}