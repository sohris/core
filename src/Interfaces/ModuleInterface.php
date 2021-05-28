<?php
namespace Sohris\Core\Interfaces;

use Sohris\Core\Server;

interface ModuleInterface
{

    public function __construct(Server $server);

}