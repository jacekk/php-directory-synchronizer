<?php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class SyncCommand extends Command
{
    const CHECKSUM_OFFSET = 34;

    private $src;
    private $srcFiles;

    private $dest;
    private $destFiles;

    private $fs; // Filesystem
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

    private function prepareProperties(InputInterface $input, OutputInterface $output)
    {
        $this->src = $input->getArgument('<src>');
        $this->dest = $input->getArgument('<dest>');
        $this->output = $output;
        $this->fs = new Filesystem();
    }

    private function pathsAreValid()
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

    private function processSync()
    {
        $this->srcFiles = $this->listFiles($this->src);
        $this->destFiles = $this->listFiles($this->dest);

        $filesToRemove = array_diff($this->destFiles, $this->srcFiles);
        $filesToCopy = array_diff($this->srcFiles, $this->destFiles);

        $this->removeOldFiles($filesToRemove);
        $this->copyNewFiles($filesToCopy);
        // $this->removeEmptyDirectories();
    }

    private function listFiles($directory)
    {
        $out = array();

        $finder = new Finder();
        $iterator = $finder
            ->files()
            ->in($directory);

        foreach ($iterator as $file) {
            $fullPath = $file->getRealpath();
            $fullPath = str_replace('\\', '/', $fullPath);
            $checksum = md5_file($fullPath);

            $relPath = $file->getRelativePathname();
            $relPath = str_replace('\\', '/', $relPath);

            $out[] = sprintf('%s__%s', $checksum, $relPath);
        }
        return $out;
    }

    private function removeOldFiles($files)
    {
        $list = array();
        foreach ($files as $file) {
            $fileName = substr($file, static::CHECKSUM_OFFSET);
            $path = $this->dest . $fileName;
            $list[] = $path;
        }
        try {
            $this->fs->remove($list);
            $counter = count($list);
            $this->output->writeln("removed old files: {$counter}");
        } catch (Exception $ex) {
            $this->output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
        }
    }

    private function copyNewFiles($files)
    {
        $counter = 0;
        foreach ($files as $file) {
            $fileName = substr($file, static::CHECKSUM_OFFSET);
            $srcPath = $this->src . $fileName;
            $destPath = $this->dest . $fileName;
            try {
                $this->fs->copy($srcPath, $destPath, true);
                $counter++;
            } catch (Exception $ex) {
                $this->output->writeln(sprintf('<error>Could not copy: %s</error>', $fileName));
            }
        }
        $this->output->writeln("copied new files: {$counter}");
    }
}
