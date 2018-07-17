<?php

namespace Storeman\Hash\Algorithm;

final class Sha2_256 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sha2-256';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPhpHashAlgorithmName(): string
    {
        return 'sha256';
    }
}
