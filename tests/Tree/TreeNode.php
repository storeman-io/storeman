<?php

namespace Archivr\Test\Tree;

class TreeNode extends \ArrayIterator implements \RecursiveIterator
{
    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name = null)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addChild(TreeNode $treeNode): TreeNode
    {
        $this->offsetSet($treeNode->getName(), $treeNode);

        return $this;
    }

    public function getChildByName(string $name)
    {
        return $this->offsetExists($name) ? $this->offsetGet($name) : null;
    }

    public function hasChildren(): bool
    {
        $current = $this->current();

        return $current && $current->count() > 0;
    }

    public function getChildren()
    {
        return $this->current();
    }

    public function __toString()
    {
        return $this->getName();
    }
}