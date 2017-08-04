<?php

namespace Archivr\LockAdapter;

abstract class AbstractLockAdapter implements LockAdapterInterface
{
    /**
     * @var string[]
     */
    protected $acquiredLocks = [];

    public function hasLock(string $name): bool
    {
        return in_array($name, $this->acquiredLocks);
    }

    public function __destruct()
    {
        $this->releaseAcquiredLocks();
    }

    protected function releaseAcquiredLocks()
    {
        foreach ($this->acquiredLocks as $acquiredLock)
        {
            $this->releaseLock($acquiredLock);
        }

        $this->acquiredLocks = [];
    }

    protected function getLockLabel(): string
    {
        return json_encode([
            'acquired' => time(),
            'user' => get_current_user()
        ]);
    }
}
