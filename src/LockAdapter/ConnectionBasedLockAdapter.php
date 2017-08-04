<?php

namespace Archivr\LockAdapter;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class ConnectionBasedLockAdapter extends AbstractLockAdapter
{
    /**
     * @var ConnectionAdapterInterface
     */
    protected $connectionAdapter;

    public function __construct(ConnectionAdapterInterface $connectionAdapter)
    {
        $this->connectionAdapter = $connectionAdapter;
    }

    public function isLocked(string $name): bool
    {
        return $this->connectionAdapter->exists($name . '.lock');
    }

    public function acquireLock(string $name): bool
    {
        $lockFileName = $this->getLockFileName($name);

        if (!$this->hasLock($name) && !$this->connectionAdapter->exists($lockFileName))
        {
            $this->connectionAdapter->write($lockFileName, $this->getLockLabel());

            $this->acquiredLocks[] = $name;
        }

        return $this->hasLock($name);
    }

    public function releaseLock(string $name): bool
    {
        if ($index = array_search($name, $this->acquiredLocks))
        {
            $this->connectionAdapter->unlink($this->getLockFileName($name));

            unset($this->acquiredLocks[$index]);
        }

        return !$this->hasLock($name);
    }

    protected function getLockFileName(string $lockName): string
    {
        return $lockName . '.lock';
    }
}
