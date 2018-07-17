<?php

namespace Storeman\Test\StorageAdapter;

use Storeman\Config\VaultConfiguration;
use Storeman\StorageAdapter\S3;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Test\ConfiguredMockProviderTrait;

class S3Test extends AbstractStorageAdapterTest
{
    use ConfiguredMockProviderTrait;

    protected function getStorageAdapter(): StorageAdapterInterface
    {
        if (!getenv('s3key') || !getenv('s3bucket') || !getenv('s3region'))
        {
            $this->markTestSkipped('Skipping test because required environment variables do not exist.');

            return null;
        }

        $vaultConfiguration = new VaultConfiguration($this->getConfigurationMock());
        $vaultConfiguration->setSettings([
            'key' => getenv('s3key'),
            'secret' => getenv('s3secret'),
            'bucket' => getenv('s3bucket'),
            'region' => getenv('s3region'),
        ]);

        return new S3($vaultConfiguration);
    }
}
