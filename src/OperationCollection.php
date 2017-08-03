<?php

namespace Archivr;

use Archivr\Operation\OperationInterface;

class OperationCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationInterface[]
     */
    protected $operations = [];

    public function addOperation(OperationInterface $operation): OperationCollection
    {
        $this->operations[] = $operation;

        return $this;
    }

    public function append(OperationCollection $other): OperationCollection
    {
        $this->operations = array_merge($this->operations, $other->operations);

        return $this;
    }

    /**
     * @return OperationInterface[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function count()
    {
        return count($this->operations);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->operations);
    }
}
