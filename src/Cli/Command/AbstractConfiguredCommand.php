<?php

namespace Archivr\Cli\Command;

use Archivr\Configuration;
use Archivr\ConfigurationFileReader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractConfiguredCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to use. Defaults to "archivr.json".');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $config = $this->getConfiguration($input);

        if ($config === null)
        {
            $output->writeln('<error>This does not seem to be an archive!</error>');

            return 1;
        }

        return $this->executeConfigured($input, $output, $config);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Configuration $configuration
     * @return int
     */
    abstract protected function executeConfigured(InputInterface $input, OutputInterface $output, Configuration $configuration): int;

    /**
     * Tries to read in the archive configuration either from the default path or a user provided one.
     *
     * @param InputInterface $input
     * @return Configuration
     */
    protected function getConfiguration(InputInterface $input)
    {
        $config = null;
        $reader = new ConfigurationFileReader();

        if ($input->getOption('config'))
        {
            $config = $reader->getConfiguration($input->getOption('config'));
        }
        elseif (is_file('archivr.json'))
        {
            $config = $reader->getConfiguration('archivr.json');
        }

        return $config;
    }
}
