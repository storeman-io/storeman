<?php

namespace Sync\Operation;

interface OperationInterface
{
    /**
     * Executes the operation and returns whether or not it has been successful.
     *
     * @return bool
     */
    public function execute(): bool;

    /**
     * Returns a string representation of the operation that is suitable to be used e.g. within cli interfaces.
     *
     * @return string
     */
    public function __toString(): string;
}