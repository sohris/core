<?php

use Sohris\Core\Components\ConfigLoader\Config;
use Sohris\Core\Loader;
use Sohris\Core\Server;
use Sohris\Core\Utils;

include "vendor/autoload.php";

$server = new Server();

$server->on('running', function(){
    $configs = Config::getConfiguration('system');
});
$server->run();