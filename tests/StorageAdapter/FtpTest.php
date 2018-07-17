<?php

namespace Storeman\Test\StorageAdapter;

use Storeman\Config\VaultConfiguration;
use Storeman\StorageAdapter\Ftp;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Test\ConfiguredMockProviderTrait;

class FtpTest extends AbstractStorageAdapterTest
{
    use ConfiguredMockProviderTrait;

    protected function getStorageAdapter(): StorageAdapterInterface
    {
        if (!getenv('ftpHost') || !getenv('ftpUser') || !getenv('ftpPass'))
        {
            $this->markTestSkipped('Skipping test because required environment variables do not exist.');

            return null;
        }

        $vaultConfiguration = new VaultConfiguration($this->getConfigurationMock());
        $vaultConfiguration->setSettings([
            'host' => getenv('ftpHost'),
            'username' => getenv('ftpUser'),
            'password' => getenv('ftpPass'),
        ]);

        return new Ftp($vaultConfiguration);
    }
}
