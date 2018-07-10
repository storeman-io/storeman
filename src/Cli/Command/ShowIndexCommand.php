<?php

namespace Storeman\Cli\Command;

use Storeman\Cli\Helper\DisplayIndexHelper;
use Storeman\Storeman;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowIndexCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('show-index');
        $this->setDescription('Displays an index');
        $this->addArgument('revision', InputArgument::OPTIONAL, 'Revision number, \'latest\' or \'local\'. Defaults to \'latest\'.', 'latest');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        $revision = $input->getArgument('revision');
        $index = null;

        if ($revision === 'latest')
        {
            $revision = $storeman->getLastRevision();

            if ($revision === null)
            {
                $this->consoleStyle->writeln("Could not find any past synchronizations to show.");

                return 0;
            }
        }
        elseif ($revision === 'local')
        {
            $index = $storeman->getLocalIndex();
        }
        elseif (ctype_digit($revision))
        {
            $revision = intval($revision);
        }
        else
        {
            $this->consoleStyle->error("Argument 'revision' invalid.");

            return 1;
        }

        if ($index === null)
        {
            assert(is_int($revision));

            $vaults = $storeman->getVaultContainer();
            $vault = $vaults->getPrioritizedVault($vaults->getVaultsHavingRevision($revision));

            if ($vault === null)
            {
                $this->consoleStyle->error("Could not find requested revision {$revision} in any vault.");

                return 1;
            }

            $index = $vault->getRemoteIndex($revision);
        }

        if ($index->count())
        {
            /** @var DisplayIndexHelper $displayIndexHelper */
            $displayIndexHelper = $this->getHelper('displayIndex');
            $displayIndexHelper->displayIndex($index, $output);
        }
        else
        {
            $this->consoleStyle->writeln("(Empty index)");
        }

        return 0;
    }
}
