<?php

namespace Archivr\StorageDriver;

use Archivr\AbstractFactory;
use Archivr\Exception\ConfigurationException;
use Archivr\Exception\ConflictException;
use Archivr\PathUtils;
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
            if (!($path = $vaultConfiguration->getSetting('path')))
            {
                throw new ConflictException("Missing vault config setting 'path' for vault '{$vaultConfiguration->getTitle()}'.'");
            }

            $path = PathUtils::getAbsolutePath($path);

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
