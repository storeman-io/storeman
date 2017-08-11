<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\ConnectionAdapter\FlysystemConnectionAdapter;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use LockAdapter\AbstractLockAdapterTest;

class ConnectionBasedLockAdapterTest extends AbstractLockAdapterTest
{
    use TemporaryPathGeneratorProviderTrait;

    protected function getLockAdapter(): LockAdapterInterface
    {
        $connection = new FlysystemConnectionAdapter(new Filesystem(new Local($this->getTemporaryPathGenerator()->getTemporaryDirectory())));

        return new ConnectionBasedLockAdapter($connection);
    }
}
