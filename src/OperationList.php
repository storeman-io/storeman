<?php

namespace Storeman;

class OperationList implements \Countable, \IteratorAggregate
{
    /**
     * @var OperationListItem[]
     */
    protected $items = [];

    /**
     * Adds an operation to the end of the list.
     *
     * @param OperationListItem $item
     * @return OperationList
     */
    public function add(OperationListItem $item): OperationList
    {
        $this->items[] = $item;

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
        $this->items = array_merge($this->items, $other->items);

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
        $this->items = array_merge($other->items, $this->items);

        return $this;
    }

    /**
     * @return OperationListItem[]
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->items);
    }
}
