<?php

use Sohris\Core\Loader;
use Sohris\Core\Server;
use Sohris\Core\Utils;

include "vendor/autoload.php";

$server = new Server();

$server->run();