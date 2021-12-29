<?php

namespace Sohris\Core;

use Exception;

class Utils
{

    private static $default_configs = null;
    private static $config_files = array();


    public static function loadVendorClasses()
    {
        $res = get_declared_classes();
        $autoloaderClassName = '';
        foreach ($res as $className) {
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
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath(Server::getRootDir() . DIRECTORY_SEPARATOR . "/src")));
        $phpFiles = new \RegexIterator($files, '/\.php$/');
        foreach ($phpFiles as $pf) {
            include_once $pf->getRealPath();
        }
        if (self::checkFileExists(realpath(Server::getRootDir() . DIRECTORY_SEPARATOR . "/vendor/sohris"))) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath(Server::getRootDir() . DIRECTORY_SEPARATOR . "/vendor/sohris")));
            $phpFiles = new \RegexIterator($files, '/\.php$/');
            foreach ($phpFiles as $pf) {
                include_once $pf->getRealPath();
            }
        }
    }

    public static function bytesToHuman($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function checkFileExists(string $file_path)
    {
        return file_exists($file_path);
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

    public static function getFilesInPath(string $dir): array
    {
        if (!is_dir($dir)) {
            throw new Exception("Invalid Dir ($dir)");
        }

        $files = scandir($dir);

        return array_filter($files, fn ($file) => !in_array($file, ['.', '..']));
    }

    public static function getConfigFiles(string $config)
    {
        if (!isset(self::$config_files[$config])) {
            $file = file_get_contents(Server::getRootDir() . DIRECTORY_SEPARATOR . "config/$config.json");

            if (empty($file))
                throw new Exception("Empty config '$config'");

            self::$config_files[$config] = json_decode($file, true);
        }

        return self::$config_files[$config];
    }

    
    public static function getAutoload()
    {
        return Server::getRootDir() . DIRECTORY_SEPARATOR . "/vendor/autoload.php";
    }

    public static function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
