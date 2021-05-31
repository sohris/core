<?php
namespace Sohris\Core\Interfaces;

use React\EventLoop\LoopInterface;
use Sohris\Core\Loop;
use Sohris\Core\Server;

interface ModuleInterface
{

    public function __construct(LoopInterface $loop);

    public function getName() : string;
}