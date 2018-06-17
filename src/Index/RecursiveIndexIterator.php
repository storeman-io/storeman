<?php

namespace Storeman\Index;

class RecursiveIndexIterator extends \ArrayIterator implements \RecursiveIterator
{
    /**
     * @var IndexNode
     */
    protected $indexNode;

    public function __construct(IndexNode $indexNode)
    {
        $this->indexNode = $indexNode;

        parent::__construct($indexNode->getChildren());
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return parent::current()->getIndexObject();
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return count($this->indexNode) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static(parent::current());
    }
}
