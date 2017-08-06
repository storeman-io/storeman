<?php

namespace Archivr\LockAdapter;

class DummyLockAdapter extends AbstractLockAdapter
{
    public function isLocked(string $name): bool
    {
        return $this->hasLock($name);
    }

    public function acquireLock(string $name): bool
    {
        $this->acquiredLocks[] = $name;

        return true;
    }

    public function releaseLock(string $name): bool
    {
        if (($index = array_search($name, $this->acquiredLocks)) !== false)
        {
            unset($this->acquiredLocks[$index]);
        }

        return true;
    }
}
