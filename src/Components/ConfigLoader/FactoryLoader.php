<?php

namespace Sohris\Core\Components\ConfigLoader;

use Exception;
use Sohris\Core\Components\ConfigLoader\Loaders\JsonConfig;
use Sohris\Core\Utils;

class FactoryLoader
{

    public static function getLoaderFile(string $file)
    {   
        if(!Utils::checkFileExists($file))
        {
            throw new Exception("Invalid Loader!");
        }

        $info = pathinfo($file);

        switch($info['extension'])
        {
            case "json":
                return new JsonConfig($file);
            break;
        }
        

    }

}