<?php

namespace Storeman\Test\LockAdapter;

use Storeman\LockAdapter\StorageBasedLockAdapter;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\StorageAdapter\LocalStorageAdapter;
use Storeman\Test\ConfiguredMockProviderTrait;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Config\VaultConfiguration;

class StorageBasedLockAdapterTest extends AbstractLockAdapterTest
{
    use ConfiguredMockProviderTrait;
    use TemporaryPathGeneratorProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        $configuration = $this->getConfigurationMock(['getIdentity' => 'test identity']);

        $vaultConfiguration = new VaultConfiguration($configuration);
        $vaultConfiguration->setAdapter('local');
        $vaultConfiguration->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $storageAdapter = new LocalStorageAdapter($vaultConfiguration);

        return new StorageBasedLockAdapter($configuration, $storageAdapter);
    }
}
