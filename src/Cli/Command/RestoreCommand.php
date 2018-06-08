<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Storeman\Cli\SynchronizationProgressListener;
use Storeman\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestoreCommand extends AbstractConfiguredCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('restore');
        $this->setDescription('Restores the local state from the vault state.');
        $this->addOption('revision', 'r', InputOption::VALUE_REQUIRED, 'Restore given revision. Defaults to last revision.');
        $this->addOption('vault', null, InputOption::VALUE_REQUIRED, 'Vault to use to download state from.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        $storeman = new Storeman($configuration);
        $storeman->restore(
            $input->getOption('revision') ? (int)$input->getOption('revision') : null,
            $input->getOption('vault'),
            new SynchronizationProgressListener($output)
        );

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
