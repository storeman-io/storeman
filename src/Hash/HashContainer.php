<?php

namespace Storeman\Hash;

final class HashContainer implements \Countable, \IteratorAggregate, \Serializable
{
    /**
     * @var array
     */
    protected $map = [];

    public function addHash(string $algorithm, string $hash): HashContainer
    {
        if (array_key_exists($algorithm, $this->map) && $this->map[$algorithm] !== $hash)
        {
            throw new \LogicException("Trying to update existing hash with different hash for the algorithm '{$algorithm}'");
        }

        $this->map[$algorithm] = $hash;

        return $this;
    }

    public function hasHash(string $algorithm): bool
    {
        return array_key_exists($algorithm, $this->map);
    }

    public function getHash(string $algorithm): ?string
    {
        return array_key_exists($algorithm, $this->map) ? $this->map[$algorithm] : null;
    }

    /**
     * Compares the given instance to this instance.
     * They are called equal if the intersection of known hash values is equal.
     *
     * @param HashContainer $other
     * @return bool
     */
    public function equals(?HashContainer $other): bool
    {
        if ($other === null)
        {
            return false;
        }

        return array_intersect_key($this->map, $other->map) == array_intersect_key($other->map, $this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        return serialize($this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): HashContainer
    {
        $this->map = unserialize($serialized);

        return $this;
    }

    public function __toString(): string
    {
        return implode(', ', array_map(function(string $algorithm, string $hash) {

            return "{$algorithm}: {$hash}";

        }, array_keys($this->map), $this->map)) ?: '-';
    }
}
