<?php

namespace Storeman\Operation;

use Storeman\StorageAdapter\StorageAdapterInterface;

/**
 * Represents entries in an ordered list of operations that have to be executed in order to perform some action.
 */
interface OperationInterface
{
    /**
     * Executes the operation and returns whether or not it has been successful.
     *
     * @param string $localBasePath
     * @param StorageAdapterInterface $storageAdapter
     * @return bool
     */
    public function execute(string $localBasePath, StorageAdapterInterface $storageAdapter): bool;

    /**
     * Returns a string representation of the operation that is suitable to be used e.g. within cli interfaces.
     *
     * @return string
     */
    public function __toString(): string;
}
