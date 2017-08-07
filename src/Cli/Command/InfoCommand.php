<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\Application;
use Archivr\Configuration;
use Archivr\Synchronization;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('info');
        $this->setDescription('Displays information about a vault and its local representation.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use. Defaults to "archivr.json".');
    }

    protected function executePrepared(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        $archivr = new ArchivR($configuration);

        $output->writeln(Application::LOGO);

        $this->displayGeneralInfo($archivr, $output);
        $this->displaySynchronizationHistory($archivr, $output);
        $this->displayOutstandingOperations($archivr, $output);

        return 0;
    }

    protected function displayGeneralInfo(ArchivR $archivR, OutputInterface $output)
    {
        $config = $archivR->getConfiguration();

        $table = new Table($output);
        $table->setStyle('compact');
        $table->addRow(['Base path:', sprintf('<info>%s</info>', $config->getLocalPath())]);
        $table->addRow(['Excluded:', implode(',', $config->getExclusions()) ?: '-']);
        $table->addRow(['Identity:', sprintf('<bold>%s</bold>', $config->getIdentity())]);

        foreach ($config->getConnectionConfigurations() as $index => $connectionConfiguration)
        {
            $table->addRow([
                sprintf('Vault <bold>#%d</bold>', $index),
                sprintf(
                    "Title: <bold>%s</bold>\nAdapter: <bold>%s</bold>\nLocking: <bold>%s</bold>\nSettings: <bold>%s</bold>",
                    $connectionConfiguration->getTitle(),
                    $connectionConfiguration->getVaultAdapter(),
                    $connectionConfiguration->getLockAdapter(),
                    implode(
                        ',',
                        array_map(
                            function($key, $value) { return "{$key}: {$value}"; },
                            array_keys($connectionConfiguration->getSettings()),
                            array_values($connectionConfiguration->getSettings())
                        )
                    )
                )
            ]);
        }

        $table->render();
    }

    protected function displaySynchronizationHistory(ArchivR $archivR, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Last synchronizations (recent first):');

        $history = array_reverse($archivR->buildSynchronizationHistory(), true);

        $table = new Table($output);
        $table->setHeaders(['Revision', 'Time (Start)', 'Identity', 'Vault(s)']);

        foreach ($history as $revision => $synchronizations)
        {
            $time = \DateTime::createFromFormat('U', 0);
            $identity = null;

            foreach ($synchronizations as $vaultTitle => $synchronization)
            {
                /** @var Synchronization $synchronization */

                $identity = $synchronization->getIdentity();
                $time = max($time, $synchronization->getTime());
            }

            $table->addRow([
                $revision,
                $time->format('r'),
                $identity,
                implode(',', array_unique(array_keys($synchronizations)))
            ]);
        }

        $table->render();
    }

    protected function displayOutstandingOperations(ArchivR $archivR, OutputInterface $output)
    {
        $output->writeln('');

        $operationCollection = $archivR->buildOperationCollection();

        if (count($operationCollection))
        {
            $output->writeln(sprintf('Current state: <bold>There are %d outstanding operations.</bold>', count($operationCollection)));
        }
        else
        {
            $output->writeln('Current state: <info>Everything is up to date!</info>');
        }
    }
}
