<?php

namespace LockAdapter\Test\LockAdapter;

use Archivr\ConnectionAdapter\FlysystemConnectionAdapter;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ConnectionBasedLockAdapterTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testLocking()
    {
        $connection = new FlysystemConnectionAdapter(new Filesystem(new Local($this->getTemporaryPathGenerator()->getTemporaryDirectory())));
        $adapter = new ConnectionBasedLockAdapter($connection);

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
