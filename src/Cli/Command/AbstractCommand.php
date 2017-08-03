<?php

namespace Archivr\Cli\Command;

use Archivr\Configuration;
use Archivr\ConfigurationFactory\JsonFileConfigurationFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected function getConfiguration(InputInterface $input, OutputInterface $output): Configuration
    {
        $configuration = $this->readConfigurationFile($input->getOption('config'));

        if ($configuration === null)
        {
            $output->writeln(sprintf('This does not seem to be an archive!'));

            exit(1);
        }

        return $configuration;
    }

    protected function readConfigurationFile(string $path = null)
    {
        if ($path === null)
        {
            foreach (['archivr.json'] as $defaultPath)
            {
                if (is_file($defaultPath))
                {
                    $path = $defaultPath;

                    break;
                }
            }

            if ($path === null)
            {
                return null;
            }
        }

        if (preg_match('/\.json/', $path))
        {
            $factory = new JsonFileConfigurationFactory($path);

            return $factory();
        }
        else
        {
            throw new \InvalidArgumentException(sprintf('Don\'t know how to handle configuration given file %s.', $path));
        }
    }
}
