<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractFactory;
use Archivr\StorageDriver\StorageDriverInterface;
use Archivr\VaultConfiguration;

class LockAdapterFactory extends AbstractFactory
{
    protected static $requiresInstanceOf = LockAdapterInterface::class;

    public function __construct()
    {
        $this->factoryMap['storage'] = function(VaultConfiguration $vaultConfiguration, StorageDriverInterface $storageDriver)
        {
            return new StorageBasedLockAdapter($storageDriver);
        };
    }
}
