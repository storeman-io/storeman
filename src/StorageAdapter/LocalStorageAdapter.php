<?php

namespace Storeman\StorageAdapter;

use Storeman\Exception\ConfigurationException;
use Storeman\PathUtils;
use Storeman\VaultConfiguration;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class LocalStorageAdapter extends FlysystemStorageAdapter
{
    public function __construct(VaultConfiguration $vaultConfiguration)
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

        parent::__construct($filesystem);
    }
}
