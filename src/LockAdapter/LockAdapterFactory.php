<?php

namespace Storeman\LockAdapter;

use Storeman\AbstractFactory;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\VaultConfiguration;

final class LockAdapterFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return LockAdapterInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['storage'] = function(VaultConfiguration $vaultConfiguration, StorageAdapterInterface $storageAdapter)
        {
            return new StorageBasedLockAdapter($storageAdapter);
        };

        return $return;
    }
}
