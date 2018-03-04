<?php

namespace Archivr\StorageDriver;

use Archivr\AbstractFactory;
use Archivr\Exception\ConfigurationException;
use Archivr\TildeExpansion;
use Archivr\VaultConfiguration;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class StorageDriverFactory extends AbstractFactory
{
    protected static $requiresInstanceOf = StorageDriverInterface::class;

    public function __construct()
    {
        $this->factoryMap['dummy'] = function ()
        {
            return new DummyStorageDriver();
        };

        $this->factoryMap['local'] = function(VaultConfiguration $vaultConfiguration)
        {
            $path = TildeExpansion::expand($vaultConfiguration->getSetting('path'));

            if (!is_dir($path) || !is_writable($path))
            {
                throw new ConfigurationException(sprintf('Path "%s" does not exist or is not writable.', $path));
            }

            $adapter = new Local($path);
            $filesystem = new Filesystem($adapter);

            return new FlysystemStorageDriver($filesystem);
        };
    }
}
