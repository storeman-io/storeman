<?php

namespace Storeman\LockAdapter;

use Storeman\Config\Configuration;
use Storeman\StorageAdapter\StorageAdapterInterface;

class StorageBasedLockAdapter extends AbstractLockAdapter
{
    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    public function __construct(Configuration $configuration, StorageAdapterInterface $storageAdapter)
    {
        parent::__construct($configuration);

        $this->storageAdapter = $storageAdapter;
    }

    protected function doGetLock(string $name): ?Lock
    {
        if (!$this->storageAdapter->exists($this->getLockFileName($name)))
        {
            return null;
        }

        $stream = $this->storageAdapter->getReadStream($this->getLockFileName($name));

        $lock = Lock::fromPayload(stream_get_contents($stream));

        fclose($stream);

        return $lock;
    }

    protected function doAcquireLock(string $name, int $timeout = null): bool
    {
        $lockFileName = $this->getLockFileName($name);
        $payload = $this->getNewLockPayload($name);

        $payloadStream = fopen('php://temp', 'r+');
        fwrite($payloadStream, $payload);
        rewind($payloadStream);

        $started = time();

        while(true)
        {
            $this->logger->debug("Checking lock existence for '{$name}'...");

            if (!$this->storageAdapter->exists($lockFileName))
            {
                $this->logger->debug("Lock '{$name}' is not taken");
                $this->logger->debug("Writing lock file {$lockFileName}...");

                $this->storageAdapter->writeStream($lockFileName, $payloadStream);

                return true;
            }

            $this->logger->debug("Lock '{$name}' is taken");

            // timeout not reached: sleep another round and try againg
            if ($timeout === null || ($started + $timeout) < time())
            {
                $this->logger->debug('Waiting for retry...');

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
