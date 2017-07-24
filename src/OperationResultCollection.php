<?php

namespace Archivr;

class OperationResultCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationResult[]
     */
    protected $operationResults = [];

    public function addOperationResult(OperationResult $operationResult): OperationResultCollection
    {
        $this->operationResults[] = $operationResult;

        return $this;
    }

    /**
     * @return OperationResult[]
     */
    public function getOperationResults(): array
    {
        return $this->operationResults;
    }

    public function count()
    {
        return count($this->operationResults);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->operationResults);
    }
}