<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\LockAdapter\DummyLockAdapter;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class DummyLockAdapterTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testLocking()
    {
        $adapter = new DummyLockAdapter();

        $this->assertFalse($adapter->isLocked('x'));
        $this->assertFalse($adapter->hasLock('x'));

        $this->assertTrue($adapter->acquireLock('x'));

        $this->assertTrue($adapter->isLocked('x'));
        $this->assertTrue($adapter->hasLock('x'));

        $this->assertFalse($adapter->isLocked('y'));
        $this->assertFalse($adapter->hasLock('y'));

        $this->assertTrue($adapter->releaseLock('x'));

        $this->assertFalse($adapter->isLocked('x'));
        $this->assertFalse($adapter->hasLock('x'));
    }
}
