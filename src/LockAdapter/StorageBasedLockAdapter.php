<?php

namespace Archivr\LockAdapter;

use Archivr\StorageDriver\StorageDriverInterface;

class StorageBasedLockAdapter extends AbstractLockAdapter
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

    protected function doAcquireLock(string $name, int $timeout = null): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $payload = $this->getNewLockPayload($name);

        $started = time();

        while(true)
        {
            if (!$this->storageDriver->exists($lockFileName))
            {
                $this->storageDriver->write($lockFileName, $payload);

                return true;
            }

            // timeout not reached: sleep another round and try againg
            if ($timeout === null || ($started + $timeout) < time())
            {
                sleep(3);
            }

            // timeout reached: return false
            else
            {
                return false;
            }
        }

        return false;
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
