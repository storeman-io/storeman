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

    protected function doesLockExist(string $name): bool
    {
        return $this->connectionAdapter->exists($name . '.lock');
    }

    protected function doAcquireLock(string $name): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $lockLabel = $this->getLockLabel();

        if ($this->connectionAdapter->exists($lockFileName))
        {
            return false;
        }

        $this->connectionAdapter->write($lockFileName, $lockLabel);

        return true;
    }

    protected function doReleaseLock(string $name)
    {
        $this->connectionAdapter->unlink($this->getLockFileName($name));
    }

    protected function getLockFileName(string $lockName): string
    {
        return $lockName . '.lock';
    }
}
