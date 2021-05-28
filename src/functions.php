<?php

namespace Sohris\Core;


function loadVendorClasses()
{
    $res = get_declared_classes();
    $autoloaderClassName = '';
    foreach ( $res as $className) {
        if (strpos($className, 'ComposerAutoloaderInit') === 0) {
            $autoloaderClassName = $className; // ComposerAutoloaderInit323a579f2019d15e328dd7fec58d8284 for me
            break;
        }
    }
    $classLoader = $autoloaderClassName::getLoader();
    
    foreach ($classLoader->getClassMap() as $path) {
       require_once $path;
    }
}

function loadLocalClasses()
{
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . 'src'));
    $phpFiles = new \RegexIterator($files, '/\.php$/');
    foreach ($phpFiles as $pf) {
        include_once $pf->getRealPath();
    }
}