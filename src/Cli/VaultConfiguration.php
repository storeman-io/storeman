<?php

namespace Storeman\Cli;

class VaultConfiguration extends \Storeman\VaultConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected $conflictHandler = 'consolePrompt';
}
