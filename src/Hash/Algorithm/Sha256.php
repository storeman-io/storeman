<?php

namespace Storeman\Hash\Algorithm;

final class Sha256 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sha256';
    }
}
