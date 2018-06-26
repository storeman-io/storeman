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
        $this->context = hash_init($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function digest(string $buffer): void
    {
        assert(is_resource($this->context), sprintf('Calling %s() on uninitialized context.', __FUNCTION__));

        hash_update($this->context, $buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(): string
    {
        assert(is_resource($this->context), sprintf('Calling %s() on uninitialized context.', __FUNCTION__));

        return hash_final($this->context);
    }
}
