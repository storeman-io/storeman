<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Storeman\Cli\Application;
use Storeman\Configuration;
use Storeman\Synchronization;
use Storeman\Vault;
use Storeman\VaultConfiguration;
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
        $storeman = new Storeman($configuration);

        $output->writeln(Application::LOGO);

        $this->displayGeneralInfo($storeman, $output);
        $this->displaySynchronizationHistory($storeman, $output);
        $this->displayOutstandingOperations($storeman, $output);

        return 0;
    }

    protected function displayGeneralInfo(Storeman $archivR, OutputInterface $output)
    {
        $config = $archivR->getConfiguration();

        $table = new Table($output);
        $table->setStyle('compact');
        $table->addRow(['Base path:', sprintf('<info>%s</info>', $config->getPath())]);
        $table->addRow(['Excluded:', implode(',', $config->getExclude()) ?: '-']);
        $table->addRow(['Identity:', $config->getIdentity()]);

        foreach (array_values($config->getVaults()) as $index => $vaultConfiguration)
        {
            /** @var VaultConfiguration $vaultConfiguration */

            $vault = $archivR->getVault($vaultConfiguration->getTitle());
            $currentLock = $vault->getLockAdapter()->getLock(Vault::LOCK_SYNC);

            $table->addRow([' ']); // blank line
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

    protected function displaySynchronizationHistory(Storeman $archivR, OutputInterface $output)
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

    protected function displayOutstandingOperations(Storeman $archivR, OutputInterface $output)
    {
        $output->writeln('');
        $output->write('Current state: ');

        $operationList = $archivR->buildOperationList();

        if ($count = count($operationList))
        {
            $output->writeln(sprintf('<bold>%d outstanding operation(s).</bold>', $count));
        }
        else
        {
            $output->writeln('<info>Everything is up to date!</info>');
        }
    }
}
