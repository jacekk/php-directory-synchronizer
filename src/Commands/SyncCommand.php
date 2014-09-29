<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Compare two directories and sync them')
            ->addArgument('<src>', InputArgument::REQUIRED, 'Source directory')
            ->addArgument('<dest>', InputArgument::REQUIRED, 'Destination directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $srcDir = $input->getArgument('<src>');
        $destDir = $input->getArgument('<dest>');

        $output->writeln('args loaded');
    }
}
