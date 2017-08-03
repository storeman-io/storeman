<?php

namespace Archivr\Cli;

class Application extends \Symfony\Component\Console\Application
{
    protected $logo = <<<TXT
   ___           __   _      ___ 
  / _ | ________/ /  (_)  __/ _ \
 / __ |/ __/ __/ _ \/ / |/ / , _/
/_/ |_/_/  \__/_//_/_/|___/_/|_| 


TXT;

    public function getHelp()
    {
        return $this->logo . parent::getHelp();
    }
}