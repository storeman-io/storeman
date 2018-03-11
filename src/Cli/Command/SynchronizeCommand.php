<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\SynchronizationProgressListener;
use Archivr\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeCommand extends AbstractConfiguredCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('sync');
        $this->setDescription('Synchronizes the local state with the vault state.');
        $this->addOption('vaults', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of vault titles to synchronize with. Defaults to all configured.');
        $this->addOption('prefer-local', null, InputOption::VALUE_NONE, 'Always prefers local changes over remote changes.');
        $this->addOption('prefer-remote', null, InputOption::VALUE_NONE, 'Always prefers remote changes over local changes.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        $vaultTitles = $input->getOption('vaults') ? explode(',', $input->getOption('vaults')) : [];
        $preferLocal = $input->getOption('prefer-local');
        $preferRemote = $input->getOption('prefer-remote');

        if ($preferLocal && $preferRemote)
        {
            $output->writeln('<error>Only one of --prefer-local and --prefer-remote options can be used at the same time.</error>');

            return 1;
        }

        if ($preferLocal)
        {
            foreach ($configuration->getVaultConfigurations() as $vaultConfiguration)
            {
                $vaultConfiguration->setConflictHandler('preferLocal');
            }
        }
        elseif ($preferRemote)
        {
            foreach ($configuration->getVaultConfigurations() as $vaultConfiguration)
            {
                $vaultConfiguration->setConflictHandler('preferRemote');
            }
        }

        $archivr = new ArchivR($configuration);
        $archivr->synchronize($vaultTitles, new SynchronizationProgressListener($output));

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
