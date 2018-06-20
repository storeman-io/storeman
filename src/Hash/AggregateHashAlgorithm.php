<?php

namespace Storeman\Hash;

use Storeman\Hash\Algorithm\HashAlgorithmInterface;

final class AggregateHashAlgorithm
{
    /**
     * @var HashAlgorithmInterface[]
     */
    protected $algorithms = [];

    /**
     * @param HashAlgorithmInterface[] $algorithms
     */
    public function __construct(array $algorithms = [])
    {
        foreach ($algorithms as $algorithm)
        {
            $this->addAlgorithm($algorithm);
        }
    }

    public function addAlgorithm(HashAlgorithmInterface $algorithm): AggregateHashAlgorithm
    {
        if (array_key_exists($algorithm->getName(), $this->algorithms))
        {
            throw new \InvalidArgumentException("Trying to re-add hash algorithm named '{$algorithm->getName()}'");
        }

        $this->algorithms[$algorithm->getName()] = $algorithm;

        return $this;
    }

    public function initialize()
    {
        array_walk($this->algorithms, function(HashAlgorithmInterface $algorithm) {

            $algorithm->initialize();
        });
    }

    public function digest(string $buffer)
    {
        array_walk($this->algorithms, function(HashAlgorithmInterface $algorithm) use ($buffer) {

            $algorithm->digest($buffer);
        });
    }

    /**
     * Finalizes hash computations and returns hashes as map algorithm->hash.
     *
     * @return array
     */
    public function finalize()
    {
        $hashes = [];

        foreach ($this->algorithms as $algorithm)
        {
            $hashes[$algorithm->getName()] = $algorithm->finalize();
        }

        return $hashes;
    }
}
