<?php
namespace Sohris\Core;

use React\EventLoop\Factory;
use React\EventLoop\Loop as EventLoopLoop;
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
            self::$loop = EventLoopLoop::get();
        }

        return self::$loop;

    }

    public static function newLoop() : \React\EventLoop\LoopInterface
    {
        return EventLoopLoop::get();
    }
}