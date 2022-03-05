<?php

namespace Sohris\Core;

use Exception;

class Utils
{
    
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
        if (!is_dir($path)) {
            return [];
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $phpFiles = new \RegexIterator($files, '/\.php$/');
        return iterator_to_array($phpFiles);
    }

    public static function recursiveCreateFolder($path)
    {
        if (is_dir($path)) {
            return;
        }

        $next_recurcion = substr($path, 0 ,strripos($path, DIRECTORY_SEPARATOR));

        if (!$next_recurcion)
            return;

        self::recursiveCreateFolder($next_recurcion);

        mkdir($path);

        return;
    }
    
    public static function jsonEncodeUTF8($content)
    {
        $c = Utils::UTF8EncodeRec($content);
        $x = json_encode($c);
        return $x;
    }

    public static function jsonDecodeUTF8($content)
    {
        $x = json_decode($content);
        $c = Utils::UTF8DecodeRec($x);
        return $c;
    }

    

    public static function UTF8EncodeRec($value)
    {
        if ($value == "" || $value == null || (!$value && $value !== "0")) {
            return " ";
        }

        $newarray = array();

        if (is_array($value)) {
            foreach ($value as $key => $data) {
                $newarray[Utils::UTF8Validate($key)] = Utils::UTF8EncodeRec($data);
            }
        } else {
            return Utils::UTF8Validate($value);
        }

        return $newarray;
    }

    public static function UTF8DecodeRec($value)
    {

        if ($value == "" || $value == null || !$value) {
            return " ";
        }

        $newarray = array();

        if (is_array($value) || gettype($value) == "object") {
            foreach ($value as $key => $data) {
                $newarray[Utils::UTF8Validate($key, true)] = Utils::UTF8DecodeRec($data);
            }
        } else {
            return Utils::UTF8Validate($value, true);
        }

        return $newarray;
    }

    public static function UTF8Validate($string, $reverse = 0)
    {
        if ($reverse == 0) {

            if (preg_match('!!u', $string)) {
                return $string;
            } else {
                return utf8_encode($string);
            }

        }

        // Decoding
        if ($reverse == 1) {

            if (preg_match('!!u', $string)) {
                return utf8_decode($string);
            } else {
                return $string;
            }

        }

        return false;
    }
}
