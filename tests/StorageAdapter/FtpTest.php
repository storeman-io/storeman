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
        $vaultConfiguration = new VaultConfiguration($this->getConfigurationMock());
        $vaultConfiguration->setSettings([
            // https://dlptest.com/ftp-test/
            'host' => 'ftp.dlptest.com',
            'username' => 'dlpuser@dlptest.com',
            'password' => '3D6XZV9MKdhM5fF',
        ]);

        return new Ftp($vaultConfiguration);
    }
}
