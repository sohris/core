<?php

namespace Sohris\Core\Commands;

use Sohris\Core\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Run extends Command
{


    protected function configure(): void
    {
        $this
            ->setName("run")
            ->addArgument('dir', InputArgument::REQUIRED, 'Root Dir of Project')
            ->addArgument("include", InputArgument::OPTIONAL, "File to include in process")
            ->setDescription('Execute a Sohris Server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $include_file = $input->getArgument("include");
        
        if($include_file)
            include $include_file;

        $server = new Server($output);
        $server->setRootDir($input->getArgument("dir"));
        $server->run();
        return Command::SUCCESS;
    }
}
