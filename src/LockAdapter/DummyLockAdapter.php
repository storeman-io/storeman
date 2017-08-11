<?php

namespace Archivr\LockAdapter;

class DummyLockAdapter extends AbstractLockAdapter
{
    protected function doesLockExist(string $name): bool
    {
        return $this->hasLock($name);
    }

    protected function doAcquireLock(string $name): bool
    {
        return true;
    }

    protected function doReleaseLock(string $name)
    {
        // nop
    }
}
