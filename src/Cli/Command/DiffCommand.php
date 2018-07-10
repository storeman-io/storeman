<?php

namespace Storeman\Cli\Command;

use Storeman\Cli\Helper\DisplayIndexComparisonHelper;
use Storeman\ConflictHandler\PreferLocalConflictHandler;
use Storeman\Index\Index;
use Storeman\Storeman;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The DiffCommand can be used to display the difference of two different states
 */
class DiffCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('diff');
        $this->setDescription('Displays the difference of a revision and another revision or the current local state.');
        $this->addArgument('revision', InputArgument::OPTIONAL, 'Revision for comparison. Defaults to the last revision.');
        $this->addArgument('compareTo', InputArgument::OPTIONAL, 'Optional other revision number for index identification to compare against. Defaults to none for comparison with the current local state.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        $revision = intval($input->getArgument('revision')) ?: $storeman->getLastRevision();

        if ($revision === null)
        {
            $this->consoleStyle->writeln("Could not find any past synchronizations to compare against.");

            return 0;
        }

        $vaults = $storeman->getVaultContainer();
        $vault = $vaults->getPrioritizedVault($vaults->getVaultsHavingRevision($revision));

        if ($vault === null)
        {
            $this->consoleStyle->error("Could not find requested revision {$revision} in any vault.");

            return 1;
        }

        $index = $vault->getRemoteIndex($revision);


        $compareTo = intval($input->getArgument('compareTo')) ?: null;

        if ($compareTo === 0)
        {
            $this->consoleStyle->error("Invalid argument compareTo given.");

            return 1;
        }
        elseif ($compareTo === null)
        {
            $compareToIndex = $storeman->getLocalIndex();
        }
        else
        {
            $vault = $vaults->getPrioritizedVault($vaults->getVaultsHavingRevision($compareTo));

            if ($vault === null)
            {
                $this->consoleStyle->error("Could not find revision {$compareTo} in any vault.");

                return 1;
            }

            $compareToIndex = $vault->getRemoteIndex($compareTo);

            assert($compareToIndex instanceof Index);
        }


        $mergedIndex = $vault->getIndexMerger()->merge(new PreferLocalConflictHandler(), $index, $compareToIndex, $index);
        $diff = $index->getDifference($mergedIndex);

        if ($diff->count() > 0)
        {
            $output->writeln(sprintf("Found %d difference(s):\n", $diff->count() ?: 'No'));

            /** @var DisplayIndexComparisonHelper $helper */
            $helper = $this->getHelper('displayIndexComparison');
            $helper->displayIndexComparison($diff, $output, "r{$revision}", $compareTo ? "r{$compareTo}" : "local");
        }

        else
        {
            $output->writeln("No differences found.");
        }

        return 0;
    }
}
