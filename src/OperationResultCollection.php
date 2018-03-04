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
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->operationResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->operationResults);
    }
}
