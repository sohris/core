<?php

namespace Sohris\Core\Commands;

use React\EventLoop\Loop;
use Sohris\Core\Server;
use Sohris\Core\Utils;
use Sohris\Event\Event\EventControl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;


class Run extends Command
{


    protected function configure(): void
    {
        $this
            ->setName("run")
            ->setDescription('Execute a Sohris Server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $server = new Server($output);
        $server->setRootDir(".");
        $server->run();
        
        return Command::SUCCESS;
    }
}
