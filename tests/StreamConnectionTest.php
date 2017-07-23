<?php

namespace Archivr\Test;

use Archivr\Connection\StreamConnection;
use PHPUnit\Framework\TestCase;

class StreamConnectionTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testSimpleLocking()
    {
        $tempPath = $this->getTemporaryPathGenerator()->getTemporaryDirectory('vaultConnection');

        $connection = new StreamConnection($tempPath);

        $this->assertFalse($connection->hasLock());
        $this->assertTrue($connection->acquireLock());
        $this->assertTrue($connection->hasLock());
        $this->assertTrue($connection->releaseLock());
        $this->assertFalse($connection->hasLock());
    }

    public function testTwoPartyLocking()
    {
        $tempPath = $this->getTemporaryPathGenerator()->getTemporaryDirectory('vaultConnection');

        $firstConnection = new StreamConnection($tempPath);
        $secondConnection = new StreamConnection($tempPath);

        $this->assertFalse($firstConnection->hasLock());
        $this->assertFalse($secondConnection->hasLock());

        $this->assertTrue($firstConnection->acquireLock());

        $this->assertTrue($firstConnection->hasLock());
        $this->assertFalse($secondConnection->hasLock());

        $this->assertFalse($secondConnection->acquireLock(false));

        $this->assertTrue($firstConnection->hasLock());
        $this->assertFalse($secondConnection->hasLock());

        $this->assertTrue($firstConnection->releaseLock());

        $this->assertFalse($firstConnection->hasLock());
        $this->assertFalse($secondConnection->hasLock());

        $this->assertTrue($secondConnection->acquireLock());

        $this->assertFalse($firstConnection->hasLock());
        $this->assertTrue($secondConnection->hasLock());

        $this->assertFalse($firstConnection->acquireLock(false));

        $this->assertFalse($firstConnection->hasLock());
        $this->assertTrue($secondConnection->hasLock());

        $this->assertTrue($secondConnection->releaseLock());

        $this->assertFalse($firstConnection->hasLock());
        $this->assertFalse($secondConnection->hasLock());
    }
}