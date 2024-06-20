<?php

namespace Sohris\Core;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use React\Stream\ReadableResourceStream;
use Sohris\Core\Exceptions\ServerException;
use Sohris\Core\Utils;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Throwable;

class Logger extends MonologLogger
{
    const DATE_FORMAT = "Y-m-d H:i:sP";
    const LOG_FORMAT = "[%datetime%][%level_name%] %message% %context%\n";


    private $log_path = './';
    private $component_name = "";

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

        $formatter = new LineFormatter(null, self::DATE_FORMAT);
        $level = Level::Alert;

        if (isset($configs['log_level'])) {
            switch ($configs['log_level']) {
                case 'debug':
                    $level = Level::Debug;
                    break;
                case 'info':
                    $level = Level::Info;
                    break;
                case 'notice':
                    $level = Level::Notice;
                    break;
                case 'warning':
                    $level = Level::Warning;
                    break;
                case 'error':
                    $level = Level::Error;
                    break;
                case 'critical':
                    $level = Level::Critical;
                    break;
                case 'alert':
                    $level = Level::Alert;
                    break;
                case 'emergency':
                    $level = Level::Emergency;
                    break;
            }
        }

        $stream = new StreamHandler($this->log_path . "/logger.log", $level);
        $stream->setFormatter($formatter);
        $error_log = new StreamHandler($this->log_path . "/error.log", Level::Error);
        $output = Server::getOutput();
        if (!$output) $output = null;
        $this->setHandlers([$stream, $error_log, new ConsoleHandler($output)]);
    }

    private function createLogFiles()
    {
        if (!is_file($this->log_path . "/logger.log")) {
            touch($this->log_path . "/logger.log");
        }

        if (!is_file($this->log_path . "/error.log")) {
            touch($this->log_path . "/error.log");
        }
    }

    public function exception(Exception $e)
    {
        $message = "Code: " . $e->getCode() . " - Message: " . $e->getMessage() . " - File: " . $e->getFile() . "(" . $e->getLine() . ")";
        $this->error($message);
    }

    public function throwable(Throwable $e)
    {
        $message = "Code: " . $e->getCode() . " - Message: " . $e->getMessage() . " - File: " . $e->getFile() . "(" . $e->getLine() . ")";
        $this->error($message);
    }
}
