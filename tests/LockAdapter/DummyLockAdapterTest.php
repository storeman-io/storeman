<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\LockAdapter\DummyLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use LockAdapter\AbstractLockAdapterTest;

class DummyLockAdapterTest extends AbstractLockAdapterTest
{
    protected function getLockAdapter(): LockAdapterInterface
    {
        return new DummyLockAdapter();
    }
}
