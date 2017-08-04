<?php

namespace Archivr;

use Ramsey\Uuid\Uuid;

class Index implements \Countable, \IteratorAggregate
{
    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var IndexObject[]
     */
    protected $pathMap = [];


    public function __construct(\DateTime $created = null)
    {
        $this->created = $created ?: new \DateTime();
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function addObject(IndexObject $indexObject): Index
    {
        // ensure existence of containing directory
        if (substr_count($indexObject->getRelativePath(), DIRECTORY_SEPARATOR))
        {
            $parent = $this->getObjectByPath(dirname($indexObject->getRelativePath()));

            if ($parent === null)
            {
                throw new \InvalidArgumentException();
            }
            elseif (!$parent->isDirectory())
            {
                throw new \InvalidArgumentException();
            }
        }

        $this->pathMap[$indexObject->getRelativePath()] = $indexObject;

        return $this;
    }

    public function getObjectByPath(string $path)
    {
        return isset($this->pathMap[$path]) ? $this->pathMap[$path] : null;
    }

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

    public function count(): int
    {
        return count($this->pathMap);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pathMap);
    }

    public function generateNewBlobId(): string
    {
        do
        {
            $blobId = Uuid::uuid4()->toString();
        }
        while ($this->getObjectByBlobId($blobId) !== null);

        return $blobId;
    }

    public function equals(Index $other = null): bool
    {
        if ($other === null)
        {
            return false;
        }

        return $this->isSubsetOf($other) && $other->isSubsetOf($this);
    }

    public function isSubsetOf(Index $other, int $flags = 0): bool
    {
        foreach ($this as $indexObject)
        {
            /** @var IndexObject $indexObject */

            $otherIndexObject = $other->getObjectByPath($indexObject->getRelativePath());

            if ($otherIndexObject === null)
            {
                return false;
            }

            if (!$otherIndexObject->equals($indexObject, $flags))
            {
                return false;
            }
        }

        return true;
    }
}
