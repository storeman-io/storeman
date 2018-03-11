<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Archivr\Cli\Application;
use Archivr\Configuration;
use Archivr\Synchronization;
use Archivr\Vault;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractConfiguredCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('info');
        $this->setDescription('Displays information about a vault and its local representation.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Configuration $configuration): int
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
        $table->addRow(['Base path:', sprintf('<info>%s</info>', $config->getPath())]);
        $table->addRow(['Excluded:', implode(',', $config->getExclude()) ?: '-']);
        $table->addRow(['Identity:', sprintf('<bold>%s</bold>', $config->getIdentity())]);

        foreach ($config->getVaults() as $index => $vaultConfiguration)
        {
            $vault = $archivR->getVault($vaultConfiguration->getTitle());
            $currentLock = $vault->getLockAdapter()->getLock(Vault::LOCK_SYNC);

            $table->addRow([
                "Vault #{$index}",
                "Title:\nAdapter:\nLock Adapter:\nSettings:\nCurrent lock:",
                implode("\n", [
                    "<info>{$vault->getVaultConfiguration()->getTitle()}</info>",
                    $vaultConfiguration->getAdapter(),
                    $vaultConfiguration->getLockAdapter(),
                    implode(
                        ',',
                        array_map(
                            function($key, $value) { return "{$key}: {$value}"; },
                            array_keys($vaultConfiguration->getSettings()),
                            array_values($vaultConfiguration->getSettings())
                        )
                    ) ?: '-',
                    $currentLock ? "Locked {$currentLock->getAcquired()->format('c')} by {$currentLock->getIdentity()}" : '-'
                ])
            ]);
        }

        $table->render();
    }

    protected function displaySynchronizationHistory(ArchivR $archivR, OutputInterface $output)
    {
        $output->writeln('');

        $history = array_reverse($archivR->buildSynchronizationHistory(), true);

        if (count($history))
        {
            $output->writeln('Last synchronizations (recent first):');

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
        else
        {
            $output->writeln('No synchronizations so far.');
        }
    }

    protected function displayOutstandingOperations(ArchivR $archivR, OutputInterface $output)
    {
        $output->writeln('');
        $output->write('Current state: ');

        $operationList = $archivR->buildOperationList();

        if ($count = count($operationList))
        {
            $output->writeln(sprintf('<bold>There are %d outstanding operations.</bold>', $count));
        }
        else
        {
            $output->writeln('<info>Everything is up to date!</info>');
        }
    }
}
