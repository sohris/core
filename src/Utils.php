<?php
namespace Sohris\Core;

use Exception;

class Utils
{

    
    public static function loadVendorClasses()
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
    
    public static function loadLocalClasses()
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath("./src")));
        $phpFiles = new \RegexIterator($files, '/\.php$/');
        foreach ($phpFiles as $pf) {
            include_once $pf->getRealPath();
        }
    }

    public static function bytesToHuman($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function checkFolder($path, $opt = null)
    {
        switch ($opt) {
            case "create":
                $paths = explode("/", $path);
                $pathname = "";
                foreach ($paths as $p) {
                    $pathname = $pathname . "$p/";
                    if (!is_dir($pathname)) {
                        mkdir($pathname);
                    }

                }
                return true;
                break;

            default:
                if (is_dir($path)) {
                    return true;
                } else {
                    return false;
                }

        }
    }

    public static function getConfig(string $config)
    {

        

    }
}
