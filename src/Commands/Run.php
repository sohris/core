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
            ->addArgument('dir', InputArgument::REQUIRED, 'Root Dir of Project')
            ->addArgument("include_files", InputArgument::IS_ARRAY, "Files to include in process")
            ->setDescription('Execute a Sohris Server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        if ($input->hasArgument("inclued_files"))
            foreach ($input->getArgument("inclued_files") as $file) {
                include $file;
            }
        $server = new Server($output);
        $server->setRootDir($input->getArgument("dir"));
        $server->run();
        return Command::SUCCESS;
    }
}
