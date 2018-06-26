<?php

namespace Storeman\LockAdapter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Storeman\Config\Configuration;

abstract class AbstractLockAdapter implements LockAdapterInterface, LoggerAwareInterface
{
    /**
     * @var int[]
     */
    protected $lockDepthMap = [];

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function isLocked(string $name): bool
    {
        return $this->hasLock($name) || $this->doGetLock($name) !== null;
    }

    public function hasLock(string $name): bool
    {
        return array_key_exists($name, $this->lockDepthMap);
    }

    public function getLock(string $name): ?Lock
    {
        return $this->doGetLock($name);
    }

    public function acquireLock(string $name, int $timeout = null): bool
    {
        $this->logger->info(sprintf(
            "Lock '{$name}' with %s timeout requested. Current lock depth: %s",
            is_int($timeout) ? "{$timeout}s" : 'indefinite',
            array_key_exists($name, $this->lockDepthMap) ? $this->lockDepthMap[$name] : '0'
        ));

        if (!array_key_exists($name, $this->lockDepthMap))
        {
            $success = $this->doAcquireLock($name, $timeout);

            if (!$success)
            {
                $this->logger->notice("Failed to acquire lock '{$name}'");

                return false;
            }

            $this->logger->notice("Successfully acquired lock '{$name}'");

            $this->lockDepthMap[$name] = 0;
        }

        $this->lockDepthMap[$name]++;

        $this->logger->info("Lock depth for '{$name}' changed to {$this->lockDepthMap[$name]}");

        return true;
    }

    public function releaseLock(string $name): bool
    {
        if (array_key_exists($name, $this->lockDepthMap))
        {
            $this->logger->info("Lock release for '{$name}' requested. Current lock depth: {$this->lockDepthMap[$name]}");

            if (--$this->lockDepthMap[$name] === 0)
            {
                $this->logger->notice("Releasing lock '{$name}'...");

                $this->doReleaseLock($name);

                unset($this->lockDepthMap[$name]);
            }
        }
        else
        {
            $this->logger->info("Requested lock release for non-hold lock '{$name}'. Doing nothing.");
        }

        return true;
    }

    public function __destruct()
    {
        $this->releaseAcquiredLocks();
    }

    protected function releaseAcquiredLocks()
    {
        $this->logger->info("Releasing all acquired locks: " . ($this->lockDepthMap ? implode(',', array_keys($this->lockDepthMap)) : '-'));

        foreach (array_keys($this->lockDepthMap) as $lockName)
        {
            $this->doReleaseLock($lockName);
        }

        $this->lockDepthMap = [];
    }

    protected function getNewLockPayload(string $name): string
    {
        return (new Lock($name, $this->configuration->getIdentity()))->getPayload();
    }

    abstract protected function doGetLock(string $name): ?Lock;
    abstract protected function doAcquireLock(string $name, int $timeout = null): bool;
    abstract protected function doReleaseLock(string $name): void;
}
