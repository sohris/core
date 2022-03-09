<?php

namespace Sohris\Core;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use React\Stream\ReadableResourceStream;
use Sohris\Core\Exceptions\ServerException;
use Sohris\Core\Utils;

class Logger extends MonologLogger
{
    const DATE_FORMAT = "Y-m-d H:i:sP";
    const LOG_FORMAT = "[%datetime%][%level_name%] %message% %context%\n";


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

        $file = $this->log_path . "/" . $this->component_name;
        $this->createLogFiles();

        parent::__construct($component_name);

        $formatter = new LineFormatter(self::LOG_FORMAT,self::DATE_FORMAT);
        $stream = new StreamHandler($file, MonologLogger::DEBUG);
        $stream->setFormatter($formatter);
        
        $this->setHandlers([$stream]);
    }

    private function createLogFiles()
    {
        if (!is_file($this->log_path . "/" . $this->component_name)) {
            touch($this->log_path . "/" . $this->component_name);
        }
    }
}
