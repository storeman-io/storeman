<?php

namespace Storeman\Operation;

use Storeman\VaultLayout\VaultLayoutInterface;

/**
 * Represents entries in an ordered list of operations that have to be executed in order to perform some action.
 */
interface OperationInterface
{
    /**
     * Executes the operation and returns whether or not it has been successful.
     *
     * @param string $localBasePath
     * @param VaultLayoutInterface $vaultLayout
     * @return bool
     */
    public function execute(string $localBasePath, VaultLayoutInterface $vaultLayout): bool;

    /**
     * Returns a string representation of the operation that is suitable to be used e.g. within cli interfaces.
     *
     * @return string
     */
    public function __toString(): string;
}
