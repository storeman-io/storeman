<?php

namespace Archivr\Cli\Command;

use Archivr\Configuration;
use Archivr\ConfigurationFileReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractCommand extends Command
{
    protected function getConfiguration(InputInterface $input): Configuration
    {
        $reader = new ConfigurationFileReader();

        if ($input->getOption('config'))
        {
            $config = $reader->getConfiguration($input->getOption('config'));
        }
        elseif (is_file('archivr.json'))
        {
            $config = $reader->getConfiguration('archivr.json');
        }
        else
        {
            return null;
        }

        $config->addExclusion('archivr.json');

        return $config;
    }
}
