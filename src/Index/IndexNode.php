<?php

namespace Storeman\Index;

/**
 * A node within the object index tree.
 */
class IndexNode implements \Countable
{
    /**
     * @var IndexObject
     */
    protected $indexObject;

    /**
     * @var IndexNode[]
     */
    protected $children = [];

    /**
     * @var IndexNode
     */
    protected $parent;

    /**
     * For the construction of a root node both arguments have to be null.
     * For the construction of regular or leaf nodes both arguments have to be given.
     *
     * @param IndexObject $indexObject
     * @param IndexNode $parent
     */
    public function __construct(IndexObject $indexObject = null, IndexNode $parent = null)
    {
        assert(($indexObject === null) === ($parent === null));

        $this->indexObject = $indexObject;
        $this->parent = $parent;
    }

    public function getIndexObject(): IndexObject
    {
        return $this->indexObject;
    }

    public function setIndexObject(IndexObject $indexObject): IndexNode
    {
        assert($this->indexObject->getRelativePath() === $indexObject->getRelativePath());

        $this->indexObject = $indexObject;

        return $this;
    }

    /**
     * Returns map from index object basename to child node.
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getParent(): ?IndexNode
    {
        return $this->parent;
    }

    public function getChild(string $name): ?IndexNode
    {
        return array_key_exists($name, $this->children) ? $this->children[$name] : null;
    }

    public function hasChild(string $name): bool
    {
        return array_key_exists($name, $this->children);
    }

    public function addChild(IndexNode $indexNode): IndexNode
    {
        assert($this->indexObject === null || $this->indexObject->isDirectory());
        assert($this->indexObject === null || strpos($indexNode->getIndexObject()->getRelativePath(), $this->indexObject->getRelativePath()) === 0);

        $this->children[$indexNode->indexObject->getBasename()] = $indexNode;

        // ensure lexicographical order
        ksort($this->children);

        return $this;
    }

    public function addChildren(array $children): IndexNode
    {
        foreach ($children as $child)
        {
            $this->addChild($child);
        }

        return $this;
    }

    public function getNodeByPath(string $path): ?IndexNode
    {
        $current = $this;

        foreach (explode(DIRECTORY_SEPARATOR, $path) as $pathPart)
        {
            $current = $current->getChild($pathPart);

            if ($current === null)
            {
                break;
            }
        }

        return $current;
    }

    /**
     * Recursively counts all children and its children and returns total.
     *
     * @return int
     */
    public function recursiveCount()
    {
        return count($this) + array_reduce($this->children, function(int $carry, IndexNode $node) {

            return $carry + $node->recursiveCount();

        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->children);
    }
}
