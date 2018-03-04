<?php

namespace Archivr\ConflictHandler;

use Archivr\AbstractFactory;

class ConflictHandlerFactory extends AbstractFactory
{
    protected static $requiresInstanceOf = ConflictHandlerInterface::class;

    public function __construct()
    {
        $this->factoryMap['panicking'] = function()
        {
            return new PanickingConflictHandler();
        };

        $this->factoryMap['preferLocal'] = function()
        {
            return new PreferLocalConflictHandler();
        };

        $this->factoryMap['preferRemote'] = function()
        {
            return new PreferRemoteConflictHandler();
        };
    }
}
