<?php

namespace LockAdapter\Test\LockAdapter;

use Storeman\Config\Configuration;
use Storeman\LockAdapter\StorageBasedLockAdapter;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\StorageAdapter\LocalStorageAdapter;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Config\VaultConfiguration;
use LockAdapter\AbstractLockAdapterTest;

class StorageBasedLockAdapterTest extends AbstractLockAdapterTest
{
    use TemporaryPathGeneratorProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        $vaultConfiguration = new VaultConfiguration($this->createMock(Configuration::class));
        $vaultConfiguration->setAdapter('local');
        $vaultConfiguration->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $storageAdapter = new LocalStorageAdapter($vaultConfiguration);

        return new StorageBasedLockAdapter($storageAdapter);
    }
}
