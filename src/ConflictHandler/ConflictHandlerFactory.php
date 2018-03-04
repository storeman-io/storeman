<?php

namespace Archivr\ConflictHandler;

use Archivr\AbstractFactory;

class ConflictHandlerFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return ConflictHandlerInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['panicking'] = function()
        {
            return new PanickingConflictHandler();
        };

        $return['preferLocal'] = function()
        {
            return new PreferLocalConflictHandler();
        };

        $return['preferRemote'] = function()
        {
            return new PreferRemoteConflictHandler();
        };

        return $return;
    }
}
