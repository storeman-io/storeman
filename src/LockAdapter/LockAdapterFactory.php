<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractFactory;
use Archivr\StorageDriver\StorageDriverInterface;
use Archivr\VaultConfiguration;

class LockAdapterFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return LockAdapterInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['storage'] = function(VaultConfiguration $vaultConfiguration, StorageDriverInterface $storageDriver)
        {
            return new StorageBasedLockAdapter($storageDriver);
        };

        return $return;
    }
}
