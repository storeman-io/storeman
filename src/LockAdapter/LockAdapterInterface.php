<?php

namespace Archivr\LockAdapter;

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
     * Tries to acquire a lock.
     * Returns true on success and false on failure.
     *
     * @param string $name
     *
     * @return bool
     */
    public function acquireLock(string $name): bool;

    /**
     * Releases an acquired lock.
     * Returns true if the lock is not in the acquired state afterwards.
     *
     * @param string $name
     *
     * @return bool
     */
    public function releaseLock(string $name): bool;
}
