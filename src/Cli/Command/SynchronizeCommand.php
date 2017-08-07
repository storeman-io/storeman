<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\SynchronizationProgressListener;
use Archivr\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('sync');
        $this->setDescription('Synchronizes the local state with the vault state.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use. Defaults to "archivr.json".');
        $this->addOption('vaults', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of vault titles to synchronize with. Defaults to all configured.');
    }

    protected function executePrepared(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        $vaultTitles = $input->getOption('vaults') ? explode(',', $input->getOption('vaults')) : [];

        $archivr = new ArchivR($configuration);
        $archivr->synchronize($vaultTitles, new SynchronizationProgressListener($output));

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
