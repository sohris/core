<?php

use Sohris\Core\Loader;
use Sohris\Core\Utils;

include "vendor/autoload.php";
Loader::loadClasses();
var_dump(Loader::getComponentsClass());
