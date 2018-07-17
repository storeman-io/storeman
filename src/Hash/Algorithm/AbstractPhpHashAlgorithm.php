<?php

namespace Storeman\Hash\Algorithm;

abstract class AbstractPhpHashAlgorithm implements HashAlgorithmInterface
{
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        $this->context = hash_init($this->getPhpHashAlgorithmName());
    }

    /**
     * {@inheritdoc}
     */
    public function digest(string $buffer): void
    {
        assert($this->context !== null, sprintf('Calling %s() on uninitialized context.', __FUNCTION__));

        hash_update($this->context, $buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(): string
    {
        assert($this->context !== null, sprintf('Calling %s() on uninitialized context.', __FUNCTION__));

        return hash_final($this->context);
    }

    /**
     * Allows to override the hash algo name.
     *
     * @return string
     */
    protected function getPhpHashAlgorithmName(): string
    {
        return $this->getName();
    }
}
