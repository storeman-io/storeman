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

    public function append(OperationResultCollection $other): OperationResultCollection
    {
        $this->operationResults = array_merge($this->operationResults, $other->operationResults);

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