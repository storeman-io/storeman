<?php

namespace Archivr\Cli\Command;

use Archivr\ArchivR;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('info');
        $this->setDescription('Displays information about a vault and its local representation.');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getConfiguration($input, $output);
        $archivr = new ArchivR($configuration);

        $operationCollection = $archivr->buildOperationCollection();

        if (count($operationCollection))
        {
            $output->writeln(sprintf('<info>There are %d outstanding operations!</info>', count($operationCollection)));
        }
        else
        {
            $output->writeln('<info>Everything is up to date!</info>');
        }
    }
}
