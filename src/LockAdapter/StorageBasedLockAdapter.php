<?php

namespace Storeman\LockAdapter;

use Storeman\StorageAdapter\StorageAdapterInterface;

class StorageBasedLockAdapter extends AbstractLockAdapter
{
    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    public function __construct(StorageAdapterInterface $storageAdapter)
    {
        parent::__construct();

        $this->storageAdapter = $storageAdapter;
    }

    protected function doGetLock(string $name): ?Lock
    {
        if (!$this->storageAdapter->exists($this->getLockFileName($name)))
        {
            return null;
        }

        return Lock::fromPayload($this->storageAdapter->read($this->getLockFileName($name)));
    }

    protected function doAcquireLock(string $name, int $timeout = null): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $payload = $this->getNewLockPayload($name);

        $started = time();

        while(true)
        {
            if (!$this->storageAdapter->exists($lockFileName))
            {
                $this->logger->debug("Writing lock file {$lockFileName}...");

                $this->storageAdapter->write($lockFileName, $payload);

                return true;
            }

            // timeout not reached: sleep another round and try againg
            if ($timeout === null || ($started + $timeout) < time())
            {
                $this->logger->debug('Sleeping...');

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

    protected function doReleaseLock(string $name): void
    {
        $this->logger->debug("Removing lock file {$this->getLockFileName($name)}...");

        $this->storageAdapter->unlink($this->getLockFileName($name));
    }

    protected function getLockFileName(string $lockName): string
    {
        return $lockName . '.lock';
    }
}
