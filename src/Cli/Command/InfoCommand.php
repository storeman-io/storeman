<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Storeman\Cli\Application;
use Storeman\Synchronization;
use Storeman\Vault;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('info');
        $this->setDescription('Displays information about a vault and its local representation.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        $output->writeln(Application::LOGO);

        $this->displayGeneralInfo($storeman, $output);
        $this->displaySynchronizationHistory($storeman, $output);

        return 0;
    }

    protected function displayGeneralInfo(Storeman $storeman, OutputInterface $output)
    {
        $config = $storeman->getConfiguration();

        $table = new Table($output);
        $table->setStyle('compact');
        $table->addRow(['Base path:', sprintf('<info>%s</info>', $config->getPath())]);
        $table->addRow(['Excluded:', implode(',', $config->getExclude()) ?: '-']);
        $table->addRow(['Identity:', $config->getIdentity()]);
        $table->addRow(['Index builder:', $config->getIndexBuilder()]);

        foreach ($storeman->getVaultContainer() as $index => $vault)
        {
            /** @var Vault $vault */

            $vaultConfiguration = $vault->getVaultConfiguration();

            $currentLock = $vault->getLockAdapter()->getLock(Vault::LOCK_SYNC);

            $table->addRow([' ']); // blank line
            $table->addRow([
                "Vault #{$index}",
                "Title:\nAdapter:\nLayout:\nLock Adapter:\nSettings:\nCurrent lock:",
                implode("\n", [
                    "<info>{$vault->getVaultConfiguration()->getTitle()}</info>",
                    $vaultConfiguration->getAdapter(),
                    $vaultConfiguration->getVaultLayout(),
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

    protected function displaySynchronizationHistory(Storeman $storeman, OutputInterface $output)
    {
        $output->writeln('');

        $history = array_reverse($this->buildSynchronizationHistory($storeman), true);

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

    /**
     * Builds and returns a history of all synchronizations on record for this archive.
     *
     * @param Storeman $storeman
     * @return Synchronization[][]
     */
    protected function buildSynchronizationHistory(Storeman $storeman): array
    {
        $return = [];

        foreach ($storeman->getVaultContainer() as $vault)
        {
            /** @var Vault $vault */

            $vaultConfig = $vault->getVaultConfiguration();
            $list = $vault->loadSynchronizationList();

            foreach ($list as $synchronization)
            {
                /** @var Synchronization $synchronization */

                $return[$synchronization->getRevision()][$vaultConfig->getTitle()] = $synchronization;
            }
        }

        ksort($return);

        return $return;
    }
}
