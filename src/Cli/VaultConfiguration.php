<?php

namespace Archivr\Cli;

class VaultConfiguration extends \Archivr\VaultConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected $conflictHandler = 'consolePrompt';
}
