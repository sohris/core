<?php

namespace Sohris\Core;

use Exception;
use React\EventLoop\LoopInterface;
use Sohris\Core\Interfaces\ModuleInterface;

class Console implements ModuleInterface
{

    private $module_name = "Console";

    private $server;

    public function __construct(LoopInterface $loop)
    {
        $this->server = Server::getServer();
        //Inicia a leitura do console apenas quando o servidor estiver rodando
        $this->server->on("running", function () use ($loop) {
            
            $stdin = new \React\Stream\ReadableResourceStream(STDIN, $loop);
            $stdout = new \React\Stream\WritableResourceStream(STDOUT, $loop);

            $stdin->on('data', function ($data) use ($stdout){
                if ($stdout->isWritable()) {
                    $stdout->write(trim($this->processLine(trim($data))) . PHP_EOL);
                }
            });
        });
    }

    public function getName() : string
    {
        return $this->module_name;
    }

    private function processLine(string $line) : string
    {
        return match($line)
        {
            "status" => $this->showStatus(),
            "clear" =>  system(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'? "cls" : "clear"),
            "exit" => true,
            default => "Function not implemented" . PHP_EOL
        };
    }

    private function showStatus() : string
    {
        $status = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . Server::FILE_SYSTEM_MONITOR), true);
        try {
            $output = Utils::loadTemplate("Status", $status);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return $output;
    }
}
