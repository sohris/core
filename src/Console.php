<?php
namespace Sohris\Core;

use Exception;

class Console
{

    private $messages = array();

    public function start()
    {
        $exit = false;
        while (!$exit) {
            $line = "";
            if (PHP_OS == 'WINNT') {
                echo '$ ';
                $line = stream_get_line(STDIN, 1024, PHP_EOL);
            } else {
                $line = readline('$ ');
            }

            $exit = $this->processLine($line);
        }
        exit;
    }

    private function processLine($line)
    {
        switch ($line) {
            case "status":
                $this->showStatus();
                break;
            case "exit":
                return true;
                break;
            default:
                echo "Function not implemented";
        }
        return false;
    }

    private function showStatus()
    {

        $status = array(
            "real_memory" => Utils::bytesToHuman(memory_get_usage(false)),
            "total_memory" => Utils::bytesToHuman(memory_get_usage(true)),
            "pid" => getmypid(),
            "loadavg" => 0
            
        );

        try{
            $output = Utils::loadTemplate("Status", $status);
        }catch(Exception $e)
        {   
            echo $e->getMessage();
            return;
        }

        echo $output . PHP_EOL;


    }

}