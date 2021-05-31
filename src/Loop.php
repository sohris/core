<?php
namespace Sohris\Core;

use React\EventLoop\Factory;
use Sohris\Core\Exceptions\LoopException;

class Loop
{
    
    private static $loop;

    private static $is_running = false;

    public static function getLoop()
    {

        if(self::$is_running)
        {
            throw new LoopException("This loop alredy running");
        }

        if(is_null(self::$loop))
        {
            self::$loop = Factory::create();
        }

        return self::$loop;

    }

    public static function newLoop() : \React\EventLoop\LoopInterface
    {
        return Factory::create();
    }
}