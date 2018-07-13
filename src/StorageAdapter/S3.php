<?php

namespace Storeman\StorageAdapter;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Storeman\Config\ConfigurationException;
use Storeman\Config\VaultConfiguration;

class S3 extends FlysystemStorageAdapter
{
    public function __construct(VaultConfiguration $vaultConfiguration)
    {
        $requiredSettings = [
            'bucket',
            'key',
            'secret',
            'region',
        ];

        if ($missingSettings = array_diff($requiredSettings, array_keys(array_filter($vaultConfiguration->getSettings()))))
        {
            throw new ConfigurationException("Missing mandatory setting(s): " . implode(',', $missingSettings));
        }

        $client = new S3Client([
            'version' => '2006-03-01',
            'credentials' => [
                'key' => $vaultConfiguration->getSetting('key'),
                'secret' => $vaultConfiguration->getSetting('secret'),
            ],
            'region' => $vaultConfiguration->getSetting('region'),
        ]);
        $adapter = new AwsS3Adapter($client, $vaultConfiguration->getSetting('bucket'));
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
