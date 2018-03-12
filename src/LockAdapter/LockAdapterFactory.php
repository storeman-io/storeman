<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractFactory;
use Archivr\StorageAdapter\StorageAdapterInterface;
use Archivr\VaultConfiguration;

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
