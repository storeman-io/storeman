<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

interface OperationInterface
{
    /**
     * Executes the operation and returns whether or not it has been successful.
     *
     * @param string $localBasePath
     * @param ConnectionAdapterInterface $connection
     * @return bool
     */
    public function execute(string $localBasePath, ConnectionAdapterInterface $connection): bool;

    /**
     * Returns a string representation of the operation that is suitable to be used e.g. within cli interfaces.
     *
     * @return string
     */
    public function __toString(): string;
}
