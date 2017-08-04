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
            return $reader->getConfiguration($input->getOption('config'));
        }

        if (is_file('archivr.json'))
        {
            return $reader->getConfiguration('archivr.json');
        }

        return null;
    }
}
