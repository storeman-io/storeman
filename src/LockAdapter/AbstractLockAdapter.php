<?php

namespace Archivr\LockAdapter;

abstract class AbstractLockAdapter implements LockAdapterInterface
{
    /**
     * @var int[]
     */
    protected $lockDepthMap = [];

    public function isLocked(string $name): bool
    {
        return $this->hasLock($name) || $this->doesLockExist($name);
    }

    public function hasLock(string $name): bool
    {
        return isset($this->lockDepthMap[$name]);
    }

    public function acquireLock(string $name): bool
    {
        if (!isset($this->lockDepthMap[$name]))
        {
            $success = $this->doAcquireLock($name);

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

    protected function getLockLabel(): string
    {
        return json_encode([
            'acquired' => time(),
            'user' => get_current_user()
        ]);
    }

    abstract protected function doesLockExist(string $name): bool;
    abstract protected function doAcquireLock(string $name): bool;
    abstract protected function doReleaseLock(string $name);
}
