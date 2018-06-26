<?php

namespace Storeman\Cli;

use Storeman\Cli\Helper\DisplayIndexHelper;

class Application extends \Symfony\Component\Console\Application
{
    public const LOGO =  <<<TXT
  ____  _                                       
 / ___|| |_ ___  _ __ ___ _ __ ___   __ _ _ __  
 \___ \| __/ _ \| '__/ _ \ '_ ` _ \ / _` | '_ \ 
  ___) | || (_) | | |  __/ | | | | | (_| | | | |
 |____/ \__\___/|_|  \___|_| |_| |_|\__,_|_| |_|


TXT;

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return static::LOGO . parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new DisplayIndexHelper());

        return $helperSet;
    }
}
