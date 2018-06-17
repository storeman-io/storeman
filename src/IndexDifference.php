<?php

namespace Storeman;

class IndexDifference implements \Countable, \IteratorAggregate
{
    /**
     * @var IndexObjectDifference[]
     */
    protected $differences = [];

    public function addDifference(IndexObjectDifference $difference): IndexDifference
    {
        $this->differences[$difference->getRelativePath()] = $difference;

        return $this;
    }

    public function getDifference(string $path): ?IndexObjectDifference
    {
        return array_key_exists($path, $this->differences) ? $this->differences[$path] : null;
    }

    public function hasDifference(string $path): bool
    {
        return array_key_exists($path, $this->differences);
    }

    /**
     * Merges the differences of the given diff into this diff.
     * There must be an empty intersection between the path sets of both diffs.
     *
     * @param IndexDifference $other
     * @return IndexDifference
     */
    public function merge(IndexDifference $other): IndexDifference
    {
        assert(empty(array_intersect(array_keys($this->differences), array_keys($other->differences))));

        $this->differences = array_merge($this->differences, $other->differences);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->differences);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->differences);
    }
}
