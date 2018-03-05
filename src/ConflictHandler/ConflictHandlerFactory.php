<?php

namespace Archivr\ConflictHandler;

use Archivr\AbstractFactory;

final class ConflictHandlerFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return ConflictHandlerInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        return [
            'panicking' => PanickingConflictHandler::class,
            'preferLocal' => PreferLocalConflictHandler::class,
            'preferRemote' => PreferRemoteConflictHandler::class,
        ];
    }
}
