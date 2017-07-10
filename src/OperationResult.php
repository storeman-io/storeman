<?php

namespace Archivr;

use Archivr\Operation\OperationInterface;

class OperationResult
{
    protected $operation;
    protected $success;

    public function __construct(OperationInterface $operation, bool $success)
    {
        $this->operation = $operation;
        $this->success = $success;
    }

    public function getOperation(): OperationInterface
    {
        return $this->operation;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}