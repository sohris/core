<?php

namespace Sohris\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Sohris\Core\Exceptions\ServerException;
use Sohris\Core\Utils;

class Logger extends MonologLogger
{
    private $log_path = './';
    private $component_name = "";

    public function __construct(string $component_name = 'Core')
    {
        $this->component_name = $component_name;
        $configs = Utils::getConfigFiles('system');
        $this->log_path = Server::getRootDir() . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "log";
        if (array_key_exists('log_folder', $configs)) {
            $this->log_path = realpath($configs['log_folder']);
        }

        $this->createLogFiles();
        parent::__construct($component_name);

        $this->setHandlers([new StreamHandler($this->log_path . "/" . $this->component_name, MonologLogger::DEBUG)]);
    }


    private function createLogFiles()
    {

        Utils::recursiveCreateFolder($this->log_path);

        if (!is_file($this->log_path . "/" . $this->component_name)) {
            touch($this->log_path . "/" . $this->component_name);
        }
    }
}
