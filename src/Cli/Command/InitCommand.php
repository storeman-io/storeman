<?php

namespace Storeman\Cli\Command;

use Storeman\Configuration;
use Storeman\ConfigurationFileWriter;
use Storeman\PathUtils;
use Storeman\VaultConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('init');
        $this->setDescription('Sets up a local archive copy.');
        $this->addArgument('target', InputArgument::OPTIONAL, 'Local target path to write the configuration file to.', './storeman.json');
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to use as local path for the archive.');
        $this->addOption('identity', 'i', InputOption::VALUE_REQUIRED, 'Identity to be used.');
        $this->addOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Relative path exclusion(s).');
        $this->addOption('writeDefaults', null, InputOption::VALUE_NONE, 'Forces writing of default values which are omitted as a default.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $configFilePath = PathUtils::getAbsolutePath($input->getArgument('target'));
        $configFileDir = dirname($configFilePath);

        if (!is_writable($configFileDir))
        {
            $output->writeln("<error>Cannot write to {$configFileDir}</error>");

            return 1;
        }


        $container = $this->getContainer();

        $configuration = new Configuration($input->getOption('path') ?: $this->consoleStyle->ask('Local path', '.'));
        $configuration->setIdentity($input->getOption('identity') ?: $this->consoleStyle->ask('Identity', get_current_user()));
        $configuration->setExclude($input->getOption('exclude') ?: $this->consoleStyle->askMultiple('Excluded path(s)'));

        // at least one storage driver has to be set up
        do
        {
            $vaultConfig = new VaultConfiguration($this->consoleStyle->choice('Storage driver', $container->getStorageAdapterNames()));
            $vaultConfig->setTitle($this->consoleStyle->ask('Title', $vaultConfig->getAdapter()));
            $vaultConfig->setLockAdapter($this->consoleStyle->choice('Lock adapter', $container->getLockAdapterNames(), $vaultConfig->getLockAdapter()));
            $vaultConfig->setIndexMerger($this->consoleStyle->choice('Index merger', $container->getIndexMergerNames(), $vaultConfig->getIndexMerger()));
            $vaultConfig->setConflictHandler($this->consoleStyle->choice('Conflict handler', $container->getConflictHandlerNames(), $vaultConfig->getConflictHandler()));
            $vaultConfig->setOperationListBuilder($this->consoleStyle->choice('Operation list builder', $container->getOperationListBuilderNames(), $vaultConfig->getOperationListBuilder()));

            while ($settingName = $this->consoleStyle->ask('Additional setting name'))
            {
                if ($settingValue = $this->consoleStyle->ask('Additional setting value'))
                {
                    $vaultConfig->setSetting($settingName, $settingValue);
                }
            }

            $configuration->addVault($vaultConfig);
        }
        while($this->consoleStyle->choice('Add another vault?', ['y', 'n'], 'n') === 'y');


        $skipDefaults = !$input->getOption('writeDefaults');

        $configurationFileWriter = new ConfigurationFileWriter();

        $output->writeln("The following content will be written to {$configFilePath}:");
        $output->writeln($configurationFileWriter->buildConfigurationFile($configuration, $skipDefaults));

        if ($this->consoleStyle->confirm('Continue? ', true))
        {
            $configurationFileWriter->writeConfigurationFile($configuration, $configFilePath, $skipDefaults);

            $output->writeln("<info>Successfully written config file to {$configFilePath}</info>");
        }
        else
        {
            $output->writeln("<comment>Aborted</comment>");
        }

        return 0;
    }
}
