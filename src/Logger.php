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
    private $readable_stream;

    public function __construct(string $component_name = 'Core')
    {
        $this->component_name = $component_name;
        $configs = Utils::getConfigFiles('system');
        $this->log_path = Server::getRootDir() . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "log";
        if (array_key_exists('log_folder', $configs)) {
            Utils::recursiveCreateFolder($configs['log_folder']);
            $this->log_path = realpath($configs['log_folder']);
        }

        $this->stream = fopen($this->log_path . "/" . $this->component_name, 'rw');
        $this->createLogFiles();

        parent::__construct($component_name);

        $this->setHandlers([new StreamHandler($this->stream, MonologLogger::DEBUG)]);

        $this->createEvents();
    }

    private function createEvents()
    {
        $this->createLogStream();
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
        $stream = new ReadableResourceStream($this->stream);
        if($stream->isReadable())
        {
            $this->readable_stream = $stream;
        }
    }

    public function on(string $event, callable $call)
    {
        $this->readable_stream->on($event, $call);
    }
}
