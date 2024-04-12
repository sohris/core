<?php

namespace Sohris\Core;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use React\Stream\ReadableResourceStream;
use Sohris\Core\Exceptions\ServerException;
use Sohris\Core\Utils;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

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

        $formatter = new LineFormatter(self::LOG_FORMAT, self::DATE_FORMAT);
        $level = MonologLogger::ALERT;

        if (isset($configs['log_level'])) {
            switch ($configs['log_level']) {
                case 'debug':
                    $level = MonologLogger::DEBUG;
                    break;
                case 'info':
                    $level = MonologLogger::INFO;
                    break;
                case 'notice':
                    $level = MonologLogger::NOTICE;
                    break;
                case 'warning':
                    $level = MonologLogger::WARNING;
                    break;
                case 'error':
                    $level = MonologLogger::ERROR;
                    break;
                case 'critical':
                    $level = MonologLogger::CRITICAL;
                    break;
                case 'alert':
                    $level = MonologLogger::ALERT;
                    break;
                case 'emergency':
                    $level = MonologLogger::EMERGENCY;
                    break;
            }
        }

        $stream = new StreamHandler($file, $level);
        $stream->setFormatter($formatter);
        $this->setHandlers([$stream, new ConsoleHandler(Server::getOutput())]);
    }

    private function createLogFiles()
    {
        if (!is_file($this->log_path . "/" . $this->component_name)) {
            touch($this->log_path . "/" . $this->component_name);
        }
    }
}
