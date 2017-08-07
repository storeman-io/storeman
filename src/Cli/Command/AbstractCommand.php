<?php

namespace Archivr\Cli\Command;

use Archivr\Configuration;
use Archivr\ConfigurationFileReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /**
     * @var Configuration
     */
    protected $config;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerFormatters($output);

        $config = $this->getConfiguration($input);

        if ($config === null)
        {
            $output->writeln(sprintf('This does not seem to be an archive!'));

            return 1;
        }

        return $this->executePrepared($input, $output, $config);
    }

    protected function executePrepared(InputInterface $input, OutputInterface $output, Configuration $configuration): int
    {
        return 0;
    }

    protected function getConfiguration(InputInterface $input)
    {
        if ($this->config === null)
        {
            $reader = new ConfigurationFileReader();

            if ($input->getOption('config'))
            {
                $this->config = $reader->getConfiguration($input->getOption('config'));
            }
            elseif (is_file('archivr.json'))
            {
                $this->config = $reader->getConfiguration('archivr.json');
            }
        }

        return $this->config;
    }

    protected function registerFormatters(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
    }
}
