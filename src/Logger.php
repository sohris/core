<?php

namespace Sohris\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use React\Stream\ReadableResourceStream;
use Sohris\Core\Exceptions\ServerException;
use Sohris\Core\Utils;

class Logger extends MonologLogger
{
    private $log_path = './';
    private $component_name = "";
    private $stream;

    public function __construct(string $component_name = 'Core')
    {
        $this->component_name = $component_name;
        $configs = Utils::getConfigFiles('system');
        $this->log_path = Server::getRootDir() . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "log";
        if (array_key_exists('log_folder', $configs)) {
            Utils::recursiveCreateFolder($configs['log_folder']);
            $this->log_path = realpath($configs['log_folder']);
        }

        $this->createLogFiles();
        parent::__construct($component_name);

        $this->setHandlers([new StreamHandler($this->log_path . "/" . $this->component_name, MonologLogger::DEBUG)]);
    }


    private function createLogFiles()
    {
        if (!is_file($this->log_path . "/" . $this->component_name)) {
            touch($this->log_path . "/" . $this->component_name);
        }
    }

    public function getLogFileStream()
    {
        if(!$this->stream)
        {
            $this->createLogStream();
        }

        return $this->stream;
    }

    private function createLogStream()
    {
        $stream = new ReadableResourceStream(fopen($this->log_path . "/" . $this->component_name, 'r'));
        if($stream->isReadable())
        {
            $this->stream = $stream;
        }
    }
}
