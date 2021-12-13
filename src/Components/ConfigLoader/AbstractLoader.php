<?php

namespace Sohris\Core\Components\ConfigLoader;

use Sohris\Core\Utils;

abstract class AbstractLoader
{
    
    public $content = [];
    
    public function getAllArrayConfig(): array
    {
        return $this->content;
    }

    public function getConfig(string $config_name): array
    {
        $keys = \explode(".", $config_name);

        return self::getConfigInTree($this->content, $keys);
    }

    private static function getConfigInTree(array $base = [], array $paths)
    {
        $key = \array_shift($paths);

        if(!empty($key))
        {
            if(!\key_exists($key, $base))
                return null;

            $new_base = $base[$key];

            if(\is_array($new_base))
                return self::getConfigInTree($new_base, $paths);
            
            return $new_base; 
        }

        if(!empty($paths))
            return self::getConfigInTree($base, $paths);

        return null;
        
    }

    protected function getAndCheckContentFile(string $path, string $extension)
    {
        $info = pathinfo($path);
        if($info['extension'] != $extension)
            return false;

        return file_get_contents($path);


    }

}