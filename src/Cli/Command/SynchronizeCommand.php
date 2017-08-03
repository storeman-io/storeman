<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\SynchronizationProgressListener;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('sync');
        $this->setDescription('Synchronizes the local state with the vault state.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getConfiguration($input, $output);
        $archivr = new ArchivR($configuration);

        $archivr->synchronize(new SynchronizationProgressListener($output));

        $output->writeln(PHP_EOL . '<info>Done!</info>');
    }
}
