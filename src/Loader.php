<?php

namespace Sohris\Core;

class Loader
{

    public static function loadClasses(bool $vendor = false)
    {
        self::requireLocalClasses();
        if($vendor)
            self::requireVendorClasses();
    }

    private static function requireVendorClasses()
    {
        $loader = self::getAutoloadClass();
        array_map(fn ($path) => self::requireFile($path), $loader->getClassMap());
    }

    public static function getAutoloadClass()
    {
        $autoload = array_filter(get_declared_classes(), fn ($class_name) => strpos($class_name, 'ComposerAutoloaderInit') === 0);
        return array_shift($autoload)::getLoader();
    }

    private static function requireLocalClasses()
    {
        array_map(
            fn ($path) => self::requireFile($path),
            Utils::getPHPFilesInDirectory(realpath(Server::getRootDir() . DIRECTORY_SEPARATOR . "/src"))
        );
        array_map(
            fn ($path) => self::requireFile($path),
            Utils::getPHPFilesInDirectory(realpath(Server::getRootDir() . DIRECTORY_SEPARATOR . "/vendor/sohris"))
        );
    }

    public static function getProjectSohrisClasses()
    {
        $autoload = array_filter(get_declared_classes(), fn ($class_name) => strpos($class_name, 'ComposerAutoloaderInit') === 0)[0];
        return $autoload::getLoader();
    }

    private static function requireFile(string $path)
    {
        require_once $path;
    }

    public static function getClassesWithParent(string $parent_name)
    {
        return array_filter(get_declared_classes(), fn ($class) => in_array($parent_name, class_parents($class)));
    }

    public static function getClassesWithInterface(string $interface_name)
    {
        return array_filter(get_declared_classes(), fn ($class) => in_array($interface_name, class_implements($class)));
    }
}
