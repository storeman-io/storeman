<?php

namespace LockAdapter\Test\LockAdapter;

use Storeman\LockAdapter\DummyLockAdapter;
use Storeman\LockAdapter\LockAdapterInterface;
use LockAdapter\AbstractLockAdapterTest;

class DummyLockAdapterTest extends AbstractLockAdapterTest
{
    protected function getLockAdapter(): LockAdapterInterface
    {
        return new DummyLockAdapter();
    }
}
