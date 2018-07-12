<?php

namespace Storeman;

use Storeman\Index\IndexObject;
use Storeman\Operation\OperationInterface;

class OperationListItem
{
    /**
     * @var OperationInterface
     */
    protected $operation;

    /**
     * @var IndexObject
     */
    protected $indexObject;

    public function __construct(OperationInterface $operation, ?IndexObject $indexObject = null)
    {
        $this->operation = $operation;
        $this->indexObject = $indexObject;
    }

    public function getOperation(): OperationInterface
    {
        return $this->operation;
    }

    public function getIndexObject(): ?IndexObject
    {
        return $this->indexObject;
    }
}
