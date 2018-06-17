<?php

namespace Storeman\Cli;

class VaultConfiguration extends \Storeman\Config\VaultConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected $conflictHandler = 'consolePrompt';
}
