<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Storeman\Cli\SynchronizationProgressListener;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractPreparedCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('dump');
        $this->setDescription('Dump the contents of a vault.');
        $this->addArgument('path', InputArgument::REQUIRED, 'Target path.');
        $this->addOption('revision', 'r', InputOption::VALUE_REQUIRED, 'Restore given revision. Defaults to last revision.');
        $this->addOption('vault', null, InputOption::VALUE_REQUIRED, 'Vault to use to download state from.');
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        $storeman->dump(
            $input->getArgument('path'),
            $input->getOption(  'revision') ? (int)$input->getOption('revision') : null,
            $input->getOption('vault'),
            new SynchronizationProgressListener($output)
        );

        $output->writeln(PHP_EOL . '<info>Done!</info>');

        return 0;
    }
}
