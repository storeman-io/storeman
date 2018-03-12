<?php

namespace Archivr\StorageAdapter;

use Archivr\AbstractFactory;
use Archivr\Exception\ConfigurationException;
use Archivr\PathUtils;
use Archivr\VaultConfiguration;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

final class StorageAdapterFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return StorageAdapterInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['local'] = function(VaultConfiguration $vaultConfiguration)
        {
            if (!($path = $vaultConfiguration->getSetting('path')))
            {
                throw new ConfigurationException("Missing vault config setting 'path' for vault '{$vaultConfiguration->getTitle()}'.'");
            }

            $path = PathUtils::getAbsolutePath($path);

            if (!is_dir($path) || !is_writable($path))
            {
                throw new ConfigurationException(sprintf('Path "%s" does not exist or is not writable.', $path));
            }

            $adapter = new Local($path);
            $filesystem = new Filesystem($adapter);

            return new FlysystemStorageAdapter($filesystem);
        };

        return $return;
    }
}
