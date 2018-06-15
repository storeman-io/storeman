<?php

namespace Storeman\Cli\Command;

use Storeman\Cli\ConfigurationFileReader;
use Storeman\Cli\ConflictHandler\ConsolePromptConflictHandler;
use Storeman\Cli\ConsoleStyle;
use Storeman\Configuration;
use Storeman\Container;
use Storeman\Storeman;
use Storeman\Vault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract class which provides a storeman instance to the concrete command which is already configured adequately
 * to run in a CLI environment.
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ConsoleStyle
     */
    protected $consoleStyle;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $defaultConfigFilePath = sprintf('./%s', Vault::CONFIG_FILE_NAME);

        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, "Path to configuration file. Defaults to \"{$defaultConfigFilePath}\".", $defaultConfigFilePath);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setUpIO($input, $output);

        $container = $this->getContainer();
        $config = $this->getConfiguration($container, $input);

        if ($config === null)
        {
            $output->writeln('<error>This does not seem to be an archive!</error>');

            return 1;
        }

        $storeman = new Storeman($container->injectConfiguration($config));

        return $this->executeConfigured($input, $output, $storeman);
    }

    protected function executeConfigured(InputInterface $input, OutputInterface $output, Storeman $storeman): int
    {
        throw new \LogicException(sprintf('Either %s::execute() or %s::executeConfigured() has to be implemented.', __CLASS__, __CLASS__));
    }

    protected function setUpIO(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->consoleStyle = new ConsoleStyle($input, $output);
    }

    /**
     * Builds and returns container to be used in a CLI context.
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        $container = new Container();

        $container->addConflictHandler('consolePrompt', ConsolePromptConflictHandler::class)->withArgument($this->consoleStyle);

        return $container;
    }

    /**
     * Tries to read in the archive configuration either from the default path or a user provided one.
     *
     * @param Container $container
     * @param InputInterface $input
     * @return Configuration
     */
    protected function getConfiguration(Container $container, InputInterface $input): ?Configuration
    {
        $configFilePath = $input->getOption('config');

        if ($configFilePath && is_file($configFilePath))
        {
            return (new ConfigurationFileReader($container))->getConfiguration($configFilePath);
        }

        return null;
    }
}
