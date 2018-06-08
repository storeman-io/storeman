<?php

namespace Storeman\StorageAdapter;

use Storeman\AbstractFactory;

final class StorageAdapterFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return StorageAdapterInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        return [
            'local' => LocalStorageAdapter::class,
        ];
    }
}
