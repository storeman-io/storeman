<?php

namespace Storeman\VaultLayout;

use Storeman\Index;
use Storeman\IndexObject;

class LazyLoadedIndex extends Index
{
    protected $pathMap = null;

    /**
     * @var callable
     */
    protected $indexLoader;

    public function __construct(callable $indexLoader)
    {
        $this->indexLoader = $indexLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function addObject(IndexObject $indexObject): Index
    {
        $this->loadIndex();

        return parent::addObject($indexObject);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectByPath(string $path): ?IndexObject
    {
        $this->loadIndex();

        return parent::getObjectByPath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectByBlobId(string $blobId): ?IndexObject
    {
        $this->loadIndex();

        return parent::getObjectByBlobId($blobId);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $this->loadIndex();

        return parent::count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        $this->loadIndex();

        return parent::getIterator();
    }

    /**
     * Lazy-loads the actual index from the injected loader.
     */
    protected function loadIndex()
    {
        if ($this->pathMap === null)
        {
            $index = call_user_func($this->indexLoader);

            if (!($index instanceof Index))
            {
                throw new \LogicException();
            }

            $this->pathMap = $index->pathMap;
        }
    }
}
