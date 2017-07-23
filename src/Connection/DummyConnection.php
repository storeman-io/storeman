<?php

namespace Archivr\Connection;

class DummyConnection implements ConnectionInterface
{
    protected $lockAcquired = false;

    public function hasLock(): bool
    {
        return $this->lockAcquired;
    }

    public function acquireLock(bool $wait = true, bool $force = false)
    {
        return $this->lockAcquired = true;
    }

    public function releaseLock()
    {
        return !($this->lockAcquired = false);
    }

    public function read(string $relativePath): string
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function write(string $relativePath, string $content)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function exists(string $relativePath): bool
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function unlink(string $relativePath)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function getStream(string $relativePath, string $mode)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }
}