<?php

namespace Sohris\Core\Tools\Worker;

use Exception;
use parallel\Channel;
use parallel\Channel\Error\Existence;
use parallel\Events;
use React\EventLoop\Loop;

final class ChannelController
{
    /**
     * @var Channel[]
     */
    static $channels = [];

    /**
     * @var Events
     */
    static $event_controller;

    /**
     * @var Loop
     */
    static $loop;

    static $listeners = [];

    public function __construct(string $channel_name)
    {
        self::createChannel($channel_name);
    }

    private static function createChannel(string $channel_name)
    {
        try {
            self::$channels[$channel_name] = Channel::make($channel_name, 100);
        } catch (Existence $e) {
            self::$channels[$channel_name] = Channel::open($channel_name);
        }
        if (!self::$event_controller) {
            self::$event_controller = new Events;
            self::$loop = new Loop;
            self::$event_controller->setBlocking(false);
            self::$loop->addPeriodicTimer(0.5, fn() => self::checkEvent());
        }
        self::$event_controller->addChannel(self::$channels[$channel_name]);
    }

    private static function checkEvent()
    {

        $event = self::$event_controller->poll();
        if (!$event) return;
        $channel_name = $event->source;
        if (!array_key_exists($channel_name, self::$channels)) return;
        self::$event_controller->addChannel(self::$channels[$channel_name]);
        $value = unserialize($event->value);
        if (!array_key_exists('event_name', $value)) return;

        $event_name = $value['event_name'];
        $args = [];

        if (array_key_exists('args', $value))
            $args = $value['args'];
        if (
            !array_key_exists($channel_name, self::$listeners) ||
            !array_key_exists($value['event_name'], self::$listeners[$channel_name])
        ) return;

        foreach (self::$listeners[$channel_name][$event_name] as $event) {
            $event($args);
        }
    }

    public static function send(string $channel_name, string $event_name, $args = null)
    {
        try {
            $channel = Channel::make($channel_name, Channel::Infinite);
        } catch (Existence $e) {
            $channel = Channel::open($channel_name);
        }

        $channel->send(serialize(['event_name' => $event_name, "args" => self::normalize($args)]));
        // $channel->close();
    }

    public static function on(string $channel_name, string $event_name, callable $callback)
    {
        if (!array_key_exists($channel_name, self::$channels)) self::createChannel($channel_name);
        if (!array_key_exists($channel_name, self::$listeners)) {
            self::$listeners[$channel_name] = [];
        }
        if (!array_key_exists($event_name, self::$listeners[$channel_name])) {
            self::$listeners[$channel_name][$event_name] = [];
        }
        $id = microtime();
        self::$listeners[$channel_name][$event_name][$id] = $callback;
        return $id;
    }

    public static function disable(string $channel_name, string $event_name, string $id)
    {
        if (!array_key_exists($channel_name, self::$channels)) return;
        if (!array_key_exists($channel_name, self::$listeners)) return;
        if (!array_key_exists($event_name, self::$listeners[$channel_name])) return;

        unset(self::$listeners[$channel_name][$event_name][$id]);
    }

    private static function normalize($arg)
    {
        $data = null;
        if (is_array($arg)) {
            $data = [];
            foreach ($arg as $key => $a)
                $data[$key] = self::normalize($a);
        }

        if (is_object($arg)) {
            try {
                serialize($arg);
                $data = $arg;
            } catch (Exception $e) {
                $data = get_class($arg);
            }
        }

        return $data;
    }
}
