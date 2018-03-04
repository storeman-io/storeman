<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractFactory;
use Archivr\StorageDriver\StorageDriverInterface;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;

class LockAdapterFactory extends AbstractFactory
{
    public function __construct()
    {
        $this->factoryMap['storage'] = function(VaultConfiguration $vaultConfiguration, StorageDriverInterface $storageDriver)
        {
            return new StorageBasedLockAdapter($storageDriver);
        };
    }

    public function create(string $name, ...$params)
    {
        $adapter = parent::create($name, ...$params);

        if (!($adapter instanceof LockAdapterInterface))
        {
            throw new Exception(sprintf('Factory closure for lock adapter "%s" does not return an instance of %s!', $name, LockAdapterInterface::class));
        }

        return $adapter;
    }
}
