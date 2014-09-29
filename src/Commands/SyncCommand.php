<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;


class SyncCommand extends Command
{
    private $src;
    private $dest;
    private $fs;
    private $output;

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
        $this->prepareProperties($input, $output);
        if (! $this->pathsAreValid()) {
            return;
        }
        $this->processSync();
    }

    protected function prepareProperties(InputInterface $input, OutputInterface $output)
    {
        $this->src = $input->getArgument('<src>');
        $this->dest = $input->getArgument('<dest>');
        $this->fs = new Filesystem();
        $this->output = $output;
    }

    protected function pathsAreValid()
    {
        if (! $this->fs->exists($this->src)) {
            $this->output->writeln('<error><src> is not a directory</error>');
            return false;
        }
        $this->src = rtrim($this->src, '/').'/';

        if (! $this->fs->exists($this->dest)) {
            $this->output->writeln('<error><dest> is not a directory</error>');
            return false;
        }
        $this->dest = rtrim($this->dest, '/').'/';
        return true;
    }

    protected function processSync()
    {
        $this->output->writeln('@todo implement processSync');
    }
}
