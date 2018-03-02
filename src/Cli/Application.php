<?php

namespace Archivr\Cli;

use Archivr\Cli\Command\DumpCommand;
use Archivr\Cli\Command\InfoCommand;
use Archivr\Cli\Command\RestoreCommand;
use Archivr\Cli\Command\SynchronizeCommand;

class Application extends \Symfony\Component\Console\Application
{
    const LOGO =  <<<TXT
   ___           __   _      ___ 
  / _ | ________/ /  (_)  __/ _ \
 / __ |/ __/ __/ _ \/ / |/ / , _/
/_/ |_/_/  \__/_//_/_/|___/_/|_| 


TXT;

    public function getHelp()
    {
        return static::LOGO . parent::getHelp();
    }

    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            new DumpCommand(),
            new InfoCommand(),
            new RestoreCommand(),
            new SynchronizeCommand(),
        ]);
    }
}
