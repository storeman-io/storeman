<?php

namespace Storeman\Hash\Algorithm;

final class Sha512 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sha512';
    }
}
