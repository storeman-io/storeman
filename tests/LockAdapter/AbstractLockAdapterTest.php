<?php

namespace Storeman\Test\LockAdapter;

use Storeman\LockAdapter\Lock;
use Storeman\LockAdapter\LockAdapterInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractLockAdapterTest extends TestCase
{
    public function testLocking()
    {
        $adapter = $this->getLockAdapter();

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

    public function testDeepLocking()
    {
        $adapter = $this->getLockAdapter();

        $this->assertFalse($adapter->hasLock('x'));
        $this->assertFalse($adapter->isLocked('x'));

        $adapter->acquireLock('x');

        $this->assertTrue($adapter->hasLock('x'));
        $this->assertTrue($adapter->isLocked('x'));

        $adapter->acquireLock('x');

        $this->assertTrue($adapter->hasLock('x'));
        $this->assertTrue($adapter->isLocked('x'));

        $adapter->releaseLock('x');

        $this->assertTrue($adapter->hasLock('x'));
        $this->assertTrue($adapter->isLocked('x'));

        $adapter->releaseLock('x');

        $this->assertFalse($adapter->hasLock('x'));
        $this->assertFalse($adapter->isLocked('x'));
    }

    public function testLockPayload()
    {
        $adapter = $this->getLockAdapter();
        $adapter->acquireLock('x');

        $lock = $adapter->getLock('x');

        $this->assertInstanceof(Lock::class, $lock);
        $this->assertEquals('x', $lock->getName());
        $this->assertEquals('test identity', $lock->getIdentity());
        $this->assertInstanceof(\DateTime::class, $lock->getAcquired());
        $this->assertEquals(time(), $lock->getAcquired()->getTimestamp(), '', 1.0);
    }

    abstract protected function getLockAdapter(): LockAdapterInterface;
}
