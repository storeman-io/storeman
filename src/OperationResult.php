<?php

namespace Storeman;

use Storeman\Operation\OperationInterface;

class OperationResult
{
    /**
     * @var OperationInterface
     */
    protected $operation;

    /**
     * @var bool
     */
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
