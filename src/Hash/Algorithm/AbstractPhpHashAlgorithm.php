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
        hash_update($this->context, $buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(): string
    {
        return hash_final($this->context);
    }
}
