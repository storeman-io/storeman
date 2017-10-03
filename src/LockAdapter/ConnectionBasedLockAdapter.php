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

    protected function doGetLock(string $name)
    {
        if (!$this->connectionAdapter->exists($this->getLockFileName($name)))
        {
            return null;
        }

        return Lock::fromPayload($this->connectionAdapter->read($this->getLockFileName($name)));
    }

    protected function doAcquireLock(string $name): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $payload = $this->getNewLockPayload($name);

        if ($this->connectionAdapter->exists($lockFileName))
        {
            return false;
        }

        $this->connectionAdapter->write($lockFileName, $payload);

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
