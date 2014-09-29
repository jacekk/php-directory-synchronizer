<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;


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
        $fs = new Filesystem();

        if (! $fs->exists($srcDir)) {
            $output->writeln('<error><src> is not a directory</error>');
            return;
        }
        $srcDir = rtrim($srcDir, '/').'/';

        if (! $fs->exists($destDir)) {
            $output->writeln('<error><dest> is not a directory</error>');
            return;
        }
        $destDir = rtrim($destDir, '/').'/';

        $output->writeln($srcDir);
        $output->writeln($destDir);
    }
}
