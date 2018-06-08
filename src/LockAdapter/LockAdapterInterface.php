<?php

namespace Storeman\LockAdapter;

/**
 * A LockAdapter is used to synchronize interaction with a vault across multiple users.
 * The implementation has to supported "nested" locking as the actual lock is only released after the count of calls to releaseLock() matches the count of calls to acquireLock() for a particular lock name.
 */
interface LockAdapterInterface
{
    /**
     * Returns true if the lock is present.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isLocked(string $name): bool;

    /**
     * Returns true if the adapter currently holds the lock.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasLock(string $name): bool;

    /**
     * Returns an eventually currently existing lock.
     *
     * @param string $name
     *
     * @return Lock
     */
    public function getLock(string $name);

    /**
     * Tries to acquire a lock.
     * Returns true on success and false on failure.
     *
     * @param string $name
     * @param int $timeout Timeout in seconds. Accepts null for indefinite waiting.
     *
     * @return bool
     */
    public function acquireLock(string $name, int $timeout = null): bool;

    /**
     * Releases an acquired lock.
     * Returns true if the lock is not in the acquired state afterwards.
     *
     * @param string $name
     *
     * @return bool
     */
    public function releaseLock(string $name): bool;

    /**
     * Sets an identity string to be used to identify a lock.
     *
     * @param string $identity
     *
     * @return LockAdapterInterface
     */
    public function setIdentity(string $identity): LockAdapterInterface;

    /**
     * @return string
     */
    public function getIdentity();
}
