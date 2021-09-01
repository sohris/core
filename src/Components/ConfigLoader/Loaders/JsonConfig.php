<?php 
namespace Sohris\Core\Components\ConfigLoader\Loaders;

use Exception;
use Sohris\Core\Components\ConfigLoader\AbstractLoader;
use Sohris\Core\Utils;

class JsonConfig extends AbstractLoader
{
    const EXTENSION = "json";

    private $file_content = '';

    public function __construct(string $real_path_file)
    {
        $this->file_content = $this->getAndCheckContentFile($real_path_file, self::EXTENSION);


        if(empty($this->file_content))
            throw new Exception("Invalid config file [$real_path_file]"); 
        
        $this->content = json_decode($this->file_content, true);

    }


}
