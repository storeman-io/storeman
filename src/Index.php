<?php

namespace Storeman;

use Storeman\Exception\Exception;

/**
 * As the name suggests an index is a representation of the vault at some point in time.
 * On iteration the objects are returned sorted by topological order.
 */
class Index implements \Countable, \IteratorAggregate
{
    /**
     * @var IndexNode
     */
    protected $rootNode;

    public function __construct()
    {
        $this->rootNode = new IndexNode();
    }

    /**
     * Adds the given object to the index.
     *
     * @param IndexObject $indexObject
     * @return Index
     * @throws Exception
     */
    public function addObject(IndexObject $indexObject): Index
    {
        $parentNode = $this->rootNode;

        // ensure existence of containing directory
        if (substr_count($indexObject->getRelativePath(), DIRECTORY_SEPARATOR) > 0)
        {
            $parentNode = $this->rootNode->getNodeByPath(dirname($indexObject->getRelativePath()));

            if ($parentNode === null)
            {
                throw new Exception("Trying to add object {$indexObject->getRelativePath()} without existing parent node");
            }
            elseif (!$parentNode->getIndexObject()->isDirectory())
            {
                throw new Exception("Trying to add object {$indexObject->getRelativePath()} under parent node which is not a directory");
            }
        }

        $parentNode->addChild(new IndexNode($indexObject, $parentNode));

        return $this;
    }

    /**
     * Returns an index object by a given relative path.
     *
     * @param string $path
     * @return IndexObject|null
     */
    public function getObjectByPath(string $path): ?IndexObject
    {
        $node = $this->rootNode->getNodeByPath($path);

        return $node ? $node->getIndexObject() : null;
    }

    /**
     * Returns an index object by a given blob id.
     *
     * @param string $blobId
     * @return IndexObject|null
     */
    public function getObjectByBlobId(string $blobId): ?IndexObject
    {
        foreach ($this as $object)
        {
            /** @var IndexObject $object */

            if ($object->getBlobId() === $blobId)
            {
                return $object;
            }
        }

        return null;
    }

    /**
     * Compares this index to the given index and returns the comparison result as boolean indicator.
     *
     * @param Index|null $other
     * @return bool
     */
    public function equals(Index $other = null): bool
    {
        if ($other === null)
        {
            return false;
        }

        return $this->isSubsetOf($other) && $other->isSubsetOf($this);
    }

    /**
     * Returns true if this index is a subset of the given index.
     *
     * @param Index $other
     * @return bool
     */
    public function isSubsetOf(Index $other): bool
    {
        foreach ($this as $indexObject)
        {
            /** @var IndexObject $indexObject */

            if (!$indexObject->equals($other->getObjectByPath($indexObject->getRelativePath())))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the difference between this and the given index.
     * The resulting difference contains objects from this index that are not present in or different to the
     * corresponding object in the given index and objects from the given index that do not have a correspondence in
     * this index.
     *
     * @param Index $other
     * @return IndexDifference
     */
    public function getDifference(Index $other): IndexDifference
    {
        $diff = new IndexDifference();

        $this->addDiffTo($other, $diff);
        $other->addDiffTo($this, $diff);

        return $diff;
    }

    /**
     * Merges the given index into this index instance.
     * Eventually existing objects with the same path are overridden.
     * The contents of directories under the same path are merged together.
     *
     * @param Index $other
     * @return Index
     */
    public function merge(Index $other): Index
    {
        foreach ($other as $object)
        {
            /** @var IndexObject $object */

            $existingObject = $this->getObjectByPath($object->getRelativePath());

            // merge directory contents
            if ($existingObject && $existingObject->isDirectory() && $object->isDirectory())
            {
                $existingNode = $this->rootNode->getNodeByPath($object->getRelativePath());
                $existingNode->setIndexObject($object);
                $existingNode->addChildren($other->rootNode->getNodeByPath($object->getRelativePath())->getChildren());
            }

            // add object or override existing
            else
            {
                $this->addObject($object);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->rootNode->recursiveCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new IndexIterator(new RecursiveIndexIterator($this->rootNode));
    }

    /**
     * Returns all those objects in this index that are not existent or are different in the given index.
     *
     * @param Index $other
     * @param IndexDifference $indexDifference
     * @return IndexDifference
     */
    protected function addDiffTo(Index $other, IndexDifference $indexDifference): IndexDifference
    {
        foreach ($this as $object)
        {
            /** @var IndexObject $object */

            $otherObject = $other->getObjectByPath($object->getRelativePath());

            if (!$object->equals($otherObject) && !$indexDifference->hasDifference($object->getRelativePath()))
            {
                $indexDifference->addDifference(new IndexObjectDifference($object, $otherObject));
            }
        }

        return $indexDifference;
    }
}
