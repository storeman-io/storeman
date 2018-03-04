<?php

namespace Archivr\LockAdapter;

use Archivr\StorageDriver\StorageDriverInterface;

class ConnectionBasedLockAdapter extends AbstractLockAdapter
{
    /**
     * @var StorageDriverInterface
     */
    protected $storageDriver;

    public function __construct(StorageDriverInterface $storageDriver)
    {
        $this->storageDriver = $storageDriver;
    }

    protected function doGetLock(string $name)
    {
        if (!$this->storageDriver->exists($this->getLockFileName($name)))
        {
            return null;
        }

        return Lock::fromPayload($this->storageDriver->read($this->getLockFileName($name)));
    }

    protected function doAcquireLock(string $name): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $payload = $this->getNewLockPayload($name);

        if ($this->storageDriver->exists($lockFileName))
        {
            return false;
        }

        $this->storageDriver->write($lockFileName, $payload);

        return true;
    }

    protected function doReleaseLock(string $name)
    {
        $this->storageDriver->unlink($this->getLockFileName($name));
    }

    protected function getLockFileName(string $lockName): string
    {
        return $lockName . '.lock';
    }
}
