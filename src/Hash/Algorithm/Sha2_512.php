<?php

namespace Storeman\Hash\Algorithm;

final class Sha2_512 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sha2-512';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPhpHashAlgorithmName(): string
    {
        return 'sha512';
    }
}
