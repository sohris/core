<?php
namespace Sohris\Core\Interface;

use Sohris\Core\Loop;

interface ModuleInterface
{

    public function __construct(Loop $loop);

}