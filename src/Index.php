<?php

namespace Archivr;

use Archivr\Exception\Exception;
use Ramsey\Uuid\Uuid;

/**
 * As the name suggests an index is a representation of the vault at some point in time.
 * It is implemented as a map from relative paths to object details.
 */
class Index implements \Countable, \IteratorAggregate
{
    /**
     * @var IndexObject[]
     */
    protected $pathMap = [];

    /**
     * Adds the given object to the index.
     *
     * @param IndexObject $indexObject
     * @return Index
     * @throws Exception
     */
    public function addObject(IndexObject $indexObject): Index
    {
        // ensure existence of containing directory
        if (substr_count($indexObject->getRelativePath(), DIRECTORY_SEPARATOR))
        {
            $parent = $this->getObjectByPath(dirname($indexObject->getRelativePath()));

            if ($parent === null)
            {
                throw new Exception();
            }
            elseif (!$parent->isDirectory())
            {
                throw new Exception();
            }
        }

        $this->pathMap[$indexObject->getRelativePath()] = $indexObject;

        return $this;
    }

    /**
     * Returns an index object by a given relative path.
     *
     * @param string $path
     * @return IndexObject|null
     */
    public function getObjectByPath(string $path)
    {
        return isset($this->pathMap[$path]) ? $this->pathMap[$path] : null;
    }

    /**
     * Returns an index object by a given blob id.
     *
     * @param string $blobId
     * @return IndexObject|null
     */
    public function getObjectByBlobId(string $blobId)
    {
        foreach ($this->pathMap as $object)
        {
            if ($object->getBlobId() === $blobId)
            {
                return $object;
            }
        }

        return null;
    }

    /**
     * Returns a new blob id not already present in this index.
     *
     * @return string
     */
    public function generateNewBlobId(): string
    {
        do
        {
            $blobId = Uuid::uuid4()->toString();
        }
        while ($this->getObjectByBlobId($blobId) !== null);

        return $blobId;
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

            // we explicitly want to use equality instead of identity
            if ($other->getObjectByPath($indexObject->getRelativePath()) != $indexObject)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->pathMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pathMap);
    }
}
