<?php

namespace Sohris\Core\Components;

use React\EventLoop\LoopInterface;
use Sohris\Core\AbstractComponent;
use Sohris\Core\Utils;

class Logger extends AbstractComponent
{
    private $log_dir;
    private static $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->priority = 10;
        
        Utils::getBaseConfig();
        self::$loop = $loop;
        
        
    }


    

    private static function log(string $type, string $message)
    {
        if(is_null(self::$log_dir))
            self::$log_dir = Utils::getBaseConfig();

        $date = date("Y-m-d hh:ii:ss");


    }

    public static function debug(string $message)
    {

    }

    public static function info(string $message)
    {
        
    }

    public static function message(string $message)
    {
        
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