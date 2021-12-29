<?php

namespace Sohris\Core\Components\ConfigLoader;

use Exception;
use React\EventLoop\LoopInterface;
use Sohris\Core\AbstractComponent;
use Sohris\Core\Server;
use Sohris\Core\Utils;

class Config extends AbstractComponent
{

    private $config_file = "";

    private static $configs = [];

    public function __construct()
    {
        $this->server = Server::getServer();
        $this->server->on("server.beforeStart",fn() => $this->configureConfigs());

    }

    private function configureConfigs()
    {
        $config_file = Server::getRootDir() . DIRECTORY_SEPARATOR . "/config";
        if(!$config_file || !is_dir($config_file))
        {
            throw new Exception("Can not set config_dir setup");
        }

        $this->config_file = realpath($config_file);

        foreach(Utils::getFilesInPath($this->config_file) as $file)
        {   
            $path = $this->config_file . DIRECTORY_SEPARATOR . $file;
            $info = pathinfo($path);
            self::$configs[$info['filename']] = FactoryLoader::getLoaderFile($path);
        }       
    }

    public static function getConfiguration(string $config_name ): AbstractLoader
    {
        if(key_exists($config_name, self::$configs))
            return self::$configs[$config_name];
        
        return null;
    }
}