<?php

namespace Archivr\LockAdapter;

abstract class AbstractLockAdapter implements LockAdapterInterface
{
    /**
     * @var int[]
     */
    protected $lockDepthMap = [];

    /**
     * @var string
     */
    protected $identity;

    public function isLocked(string $name): bool
    {
        return $this->hasLock($name) || $this->doGetLock($name) !== null;
    }

    public function hasLock(string $name): bool
    {
        return isset($this->lockDepthMap[$name]);
    }

    public function getLock(string $name)
    {
        return $this->doGetLock($name);
    }

    public function acquireLock(string $name, int $timeout = null): bool
    {
        if (!isset($this->lockDepthMap[$name]))
        {
            $success = $this->doAcquireLock($name, $timeout);

            if (!$success)
            {
                return false;
            }

            $this->lockDepthMap[$name] = 0;
        }

        $this->lockDepthMap[$name]++;

        return true;
    }

    public function releaseLock(string $name): bool
    {
        if (isset($this->lockDepthMap[$name]))
        {
            if (--$this->lockDepthMap[$name] === 0)
            {
                $this->doReleaseLock($name);

                unset($this->lockDepthMap[$name]);
            }
        }

        return true;
    }

    public function setIdentity(string $identity): LockAdapterInterface
    {
        $this->identity = $identity;

        return $this;
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function __destruct()
    {
        $this->releaseAcquiredLocks();
    }

    protected function releaseAcquiredLocks()
    {
        foreach (array_keys($this->lockDepthMap) as $lockName)
        {
            $this->doReleaseLock($lockName);
        }

        $this->lockDepthMap = [];
    }

    protected function getNewLockPayload(string $name): string
    {
        return (new Lock($name, $this->identity))->getPayload();
    }

    abstract protected function doGetLock(string $name);
    abstract protected function doAcquireLock(string $name, int $timeout = null): bool;
    abstract protected function doReleaseLock(string $name);
}
