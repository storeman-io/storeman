<?php

namespace Storeman\Cli\Command;

use Storeman\Storeman;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract class which provides a storeman instance to the concrete command which is already configured adequately
 * to run in a CLI environment.
 */
abstract class AbstractPreparedCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use. Defaults to "storeman.json".');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $container = $this->getContainer();
        $config = $this->getConfiguration($input);

        if ($config === null)
        {
            $output->writeln('<error>This does not seem to be an archive!</error>');

            return 1;
        }

        $storeman = new Storeman($config, $container);

        return $this->executeConfigured($input, $output, $storeman);
    }

    abstract protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int;
}
