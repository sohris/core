<?php

namespace Sohris\Core;

use Exception;

class Utils
{

    private static $default_configs = null;
    private static $config_files = array();


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

    public static function getPHPFilesInDirectory(string $path)
    {
        if(!is_dir($path))
        {
            return [];
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $phpFiles = new \RegexIterator($files, '/\.php$/');
        return iterator_to_array($phpFiles);
    }
}
