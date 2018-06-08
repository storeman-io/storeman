<?php

namespace Storeman\Cli\Command;

use Storeman\Cli\ConfigurationFileReader;
use Storeman\Cli\ConflictHandler\ConsolePromptConflictHandler;
use Storeman\Cli\ConsoleStyle;
use Storeman\Configuration;
use Storeman\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->consoleStyle = new ConsoleStyle($input, $output);
    }

    /**
     * Builds container to be used
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
     * @param InputInterface $input
     * @return Configuration
     */
    protected function getConfiguration(InputInterface $input): ?Configuration
    {
        $config = null;
        $reader = new ConfigurationFileReader();

        if ($input->getOption('config'))
        {
            $config = $reader->getConfiguration($input->getOption('config'));
        }
        elseif (is_file('storeman.json'))
        {
            $config = $reader->getConfiguration('storeman.json');
        }

        return $config;
    }
}
