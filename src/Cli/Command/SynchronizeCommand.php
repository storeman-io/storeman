<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Storeman\Cli\SynchronizationProgressListener;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeCommand extends AbstractCommand
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

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        $vaultTitles = $input->getOption('vaults') ? explode(',', $input->getOption('vaults')) : null;
        $preferLocal = $input->getOption('prefer-local');
        $preferRemote = $input->getOption('prefer-remote');

        if ($preferLocal && $preferRemote)
        {
            $output->writeln('<error>Only one of --prefer-local and --prefer-remote options can be used at the same time.</error>');

            return 1;
        }

        $configuration = $storeman->getConfiguration();

        if ($preferLocal)
        {
            foreach ($configuration->getVaults() as $vaultConfiguration)
            {
                $vaultConfiguration->setConflictHandler('preferLocal');
            }
        }
        elseif ($preferRemote)
        {
            foreach ($configuration->getVaults() as $vaultConfiguration)
            {
                $vaultConfiguration->setConflictHandler('preferRemote');
            }
        }

        $storeman->synchronize($vaultTitles, new SynchronizationProgressListener($output));

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
