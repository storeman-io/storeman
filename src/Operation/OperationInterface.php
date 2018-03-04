<?php

namespace Archivr\Operation;

use Archivr\StorageDriver\StorageDriverInterface;

interface OperationInterface
{
    /**
     * Executes the operation and returns whether or not it has been successful.
     *
     * @param string $localBasePath
     * @param StorageDriverInterface $storageDriver
     * @return bool
     */
    public function execute(string $localBasePath, StorageDriverInterface $storageDriver): bool;

    /**
     * Returns a string representation of the operation that is suitable to be used e.g. within cli interfaces.
     *
     * @return string
     */
    public function __toString(): string;
}
