<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\LockAdapter\StorageBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use Archivr\StorageAdapter\LocalStorageAdapter;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\VaultConfiguration;
use LockAdapter\AbstractLockAdapterTest;

class StorageBasedLockAdapterTest extends AbstractLockAdapterTest
{
    use TemporaryPathGeneratorProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        $vaultConfiguration = new VaultConfiguration('local');
        $vaultConfiguration->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $storageAdapter = new LocalStorageAdapter($vaultConfiguration);

        return new StorageBasedLockAdapter($storageAdapter);
    }
}
