<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\SynchronizationProgressListener;
use Archivr\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('restore');
        $this->setDescription('Restores the local state from the vault state.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use. Defaults to "archivr.json".');
        $this->addOption('revision', 'r', InputOption::VALUE_REQUIRED, 'Restore given revision. Defaults to last revision.');
        $this->addOption('vault', null, InputOption::VALUE_REQUIRED, 'Vault to use to download state from.');
    }

    protected function executePrepared(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        $vaultTitle = $input->getOption('vault') ?: $configuration->getConnectionConfigurations()[0]->getTitle();

        if (!$configuration->getConnectionConfigurationByTitle($vaultTitle))
        {
            $output->writeln(sprintf('<error>Unknown vault: %s</error>', $vaultTitle));

            return 2;
        }

        $archivr = new ArchivR($configuration);
        $archivr->restore($input->hasOption('revision') ? (int)$input->getArgument('revision') : null, new SynchronizationProgressListener($output));

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
