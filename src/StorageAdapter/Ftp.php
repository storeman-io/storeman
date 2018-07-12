<?php

namespace Storeman\StorageAdapter;

use League\Flysystem\Filesystem;
use Storeman\Config\ConfigurationException;
use Storeman\Config\VaultConfiguration;

class Ftp extends FlysystemStorageAdapter
{
    public function __construct(VaultConfiguration $vaultConfiguration)
    {
        $requiredSettings = [
            'host',
            'username',
            'password',
        ];

        $defaults = [
            'port' => 21,
        ];

        if ($missingSettings = array_diff($requiredSettings, array_keys($vaultConfiguration->getSettings())))
        {
            throw new ConfigurationException("Missing mandatory setting(s): " . implode(',', $missingSettings));
        }

        $settings = array_merge($defaults, $vaultConfiguration->getSettings());

        // atm unknown settings
        $adapter = new \League\Flysystem\Adapter\Ftp($settings);
        $filesystem = new Filesystem($adapter);

        parent::__construct($filesystem);
    }

    /**
     * {@inheritdoc}
     */
    public static function getIdentificationString(VaultConfiguration $vaultConfiguration): string
    {
        return "{$vaultConfiguration->getSetting('username')}@{$vaultConfiguration->getSetting('host')}";
    }
}
