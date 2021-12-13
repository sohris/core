<?php

namespace Sohris\Core\Components;

use React\EventLoop\LoopInterface;
use Sohris\Core\AbstractComponent;
use Sohris\Core\Utils;

class Logger extends AbstractComponent
{
    const DEBUG = 0;
    const LOW = 1;
    const MEDIUM = 2;
    const HIGH = 3;
    const CRITICAL = 4;

    private $log_dir;
    private static $log_level = null;
    private static $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->priority = 10;

        Utils::getBaseConfig();
        self::$loop = $loop;

        self::setLoggerLevel();
    }

    private static function setLoggerLevel()
    {
        if (!self::$log_level && getopt('d')) {
            self::$log_level = getopt('d');
        } else if (!self::$log_level) {
            self::$log_level = self::DEBUG;
        }
    }

    private static function log(string $type, string $message)
    {
        if (is_null(self::$log_dir))
            self::$log_dir = Utils::getBaseConfig();

        $date = date("Y-m-d hh:ii:ss");
    }

    public static function debug(string $message)
    {

        $date = date("Y-m-d h:i:s");

        echo "[$date] $message" . PHP_EOL;
    }

    public static function info(string $message)
    {
    }

    public static function message(string $message, int $level = 1)
    {
        self::setLoggerLevel();

        if ($level >= self::$log_level)
            self::log("message", $message);
    }


    public static function warning(string $message)
    {

        self::log("warning", $message);
    }


    public static function critical(string $message)
    {

        self::log("critical", $message);
    }
}
