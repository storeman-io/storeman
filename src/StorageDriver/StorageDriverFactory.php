<?php

namespace Archivr\StorageDriver;

use Archivr\AbstractFactory;
use Archivr\Exception\ConfigurationException;
use Archivr\TildeExpansion;
use Archivr\VaultConfiguration;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

final class StorageDriverFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return StorageDriverInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['dummy'] = DummyStorageDriver::class;

        $return['local'] = function(VaultConfiguration $vaultConfiguration)
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

        return $return;
    }
}
