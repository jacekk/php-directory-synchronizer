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
        $this->output->writeln("sync finished");
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
        if (empty($files)) {
            return;
        }
        $progress = $this->getHelper('progress');
        $progress->start($this->output, count($files));
        $this->output->writeln("removing old files...");
        foreach ($files as $file) {
            $fileName = substr($file, static::CHECKSUM_OFFSET);
            $path = $this->dest . $fileName;
            try {
                $this->fs->remove($path);
                $progress->advance();
            } catch (Exception $ex) {
                $this->output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
            }
        }
        $progress->finish();
    }

    private function copyNewFiles($files)
    {
        if (empty($files)) {
            return;
        }
        $progress = $this->getHelper('progress');
        $progress->start($this->output, count($files));
        $this->output->writeln("copying new files...");
        foreach ($files as $file) {
            $fileName = substr($file, static::CHECKSUM_OFFSET);
            $srcPath = $this->src . $fileName;
            $destPath = $this->dest . $fileName;
            try {
                $this->fs->copy($srcPath, $destPath, true);
                $progress->advance();
            } catch (Exception $ex) {
                $this->output->writeln(sprintf('<error>Could not copy: %s</error>', $fileName));
            }
        }
        $progress->finish();
    }
}
