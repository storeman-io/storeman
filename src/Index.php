<?php

namespace Sync;

use Ramsey\Uuid\Uuid;

class Index implements \IteratorAggregate
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

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pathMap);
    }

    public function generateNewBlobId(): string
    {
        do
        {
            $blobId = Uuid::uuid4();
        }
        while($this->getObjectByBlobId($blobId) !== null);

        return $blobId;
    }
}