<?php

namespace Storeman;

use Storeman\Operation\OperationInterface;

class OperationList implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationInterface[]
     */
    protected $operations = [];

    /**
     * Adds an operation to the end of the list.
     *
     * @param OperationInterface $operation
     * @return OperationList
     */
    public function addOperation(OperationInterface $operation): OperationList
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Appends another operation list to the end of this list.
     *
     * @param OperationList $other
     * @return OperationList
     */
    public function append(OperationList $other): OperationList
    {
        $this->operations = array_merge($this->operations, $other->operations);

        return $this;
    }

    /**
     * Prepends another operation list to the start of this list.
     *
     * @param OperationList $other
     * @return OperationList
     */
    public function prepend(OperationList $other): OperationList
    {
        $this->operations = array_merge($other->operations, $this->operations);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->operations);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->operations);
    }
}
