<?php

namespace Sohris\Core;

use Exception;
use Sohris\Core\Interfaces\ModuleInterface;

class Console implements ModuleInterface
{

    private $messages = array();

    private $stdin;
    private $stdout;
    public function __construct(Server $server)
    {

        $loop = Loop::getLoop();

        $this->stdin = new \React\Stream\ReadableResourceStream(STDIN, $loop);
        $this->stdout = new \React\Stream\WritableResourceStream(STDOUT, $loop);
        //Inicia a leitura do console apenas quando o servidor estiver rodando
        $server->on("server.running", function ()  {
            $this->stdin->on('data', function ($data) {
                if ($this->stdout->isWritable()) {
                    $this->stdout->write(trim($this->processLine(trim($data))) . PHP_EOL);
                }
            });
        });
    }

    private function processLine($line)
    {
        switch ($line) {
            case "status":
                return $this->showStatus();
                break;
            case "exit":
                return true;
                break;
            case "clear":
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    system("cls");
                    break;
                }
                system("clear");

                break;
            default:
                return "Function not implemented" . PHP_EOL;
        }
        return false;
    }

    private function showStatus()
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
