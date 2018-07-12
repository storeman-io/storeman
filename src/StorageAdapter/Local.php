<?php

namespace Storeman\StorageAdapter;

use Storeman\Config\ConfigurationException;
use Storeman\PathUtils;
use Storeman\Config\VaultConfiguration;
use League\Flysystem\Filesystem;

class Local extends FlysystemStorageAdapter
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

        $adapter = new \League\Flysystem\Adapter\Local($path);
        $filesystem = new Filesystem($adapter);

        parent::__construct($filesystem);
    }

    /**
     * {@inheritdoc}
     */
    public static function getIdentificationString(VaultConfiguration $vaultConfiguration): string
    {
        return $vaultConfiguration->getSetting('path');
    }
}
