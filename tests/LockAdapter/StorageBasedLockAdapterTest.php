<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\StorageAdapter\FlysystemStorageAdapter;
use Archivr\LockAdapter\StorageBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use LockAdapter\AbstractLockAdapterTest;

class StorageBasedLockAdapterTest extends AbstractLockAdapterTest
{
    use TemporaryPathGeneratorProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        $connection = new FlysystemStorageAdapter(new Filesystem(new Local($this->getTemporaryPathGenerator()->getTemporaryDirectory())));

        return new StorageBasedLockAdapter($connection);
    }
}
