<?php

namespace Archivr;

use Archivr\Operation\OperationInterface;

class OperationCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationInterface[]
     */
    protected $operations = [];

    /**
     * Adds an operation to the end of the list.
     *
     * @param OperationInterface $operation
     * @return OperationCollection
     */
    public function addOperation(OperationInterface $operation): OperationCollection
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Appends another operation list to the end of this list.
     *
     * @param OperationCollection $other
     * @return OperationCollection
     */
    public function append(OperationCollection $other): OperationCollection
    {
        $this->operations = array_merge($this->operations, $other->operations);

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
