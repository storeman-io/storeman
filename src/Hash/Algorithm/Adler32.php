<?php

namespace Storeman\Hash\Algorithm;

final class Adler32 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'adler32';
    }
}
