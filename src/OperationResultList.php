<?php

namespace Storeman;

class OperationResultList implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationResult[]
     */
    protected $operationResults = [];

    /**
     * Adds an operation result to the end of the list.
     *
     * @param OperationResult $operationResult
     * @return OperationResultList
     */
    public function addOperationResult(OperationResult $operationResult): OperationResultList
    {
        $this->operationResults[] = $operationResult;

        return $this;
    }

    /**
     * Appends another operation result list to the end of this list.
     *
     * @param OperationResultList $other
     * @return OperationResultList
     */
    public function append(OperationResultList $other): OperationResultList
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
