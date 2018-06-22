<?php

namespace Storeman\Index\Comparison;

class IndexComparison implements \Countable, \IteratorAggregate
{
    /**
     * @var IndexObjectComparison[]
     */
    protected $objectComparisons = [];

    public function addObjectComparison(IndexObjectComparison $objectComparison): IndexComparison
    {
        $this->objectComparisons[$objectComparison->getRelativePath()] = $objectComparison;

        return $this;
    }

    public function getObjectComparison(string $path): ?IndexObjectComparison
    {
        return array_key_exists($path, $this->objectComparisons) ? $this->objectComparisons[$path] : null;
    }

    public function hasObjectComparison(string $path): bool
    {
        return array_key_exists($path, $this->objectComparisons);
    }

    /**
     * Merges the given comparison set into this set.
     * There must be an empty intersection between the path sets of both comparisons.
     *
     * @param IndexComparison $other
     * @return IndexComparison
     */
    public function merge(IndexComparison $other): IndexComparison
    {
        assert(empty(array_intersect(array_keys($this->objectComparisons), array_keys($other->objectComparisons))));

        $this->objectComparisons = array_merge($this->objectComparisons, $other->objectComparisons);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->objectComparisons);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->objectComparisons);
    }
}
