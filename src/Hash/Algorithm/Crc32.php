<?php

namespace Storeman\Hash\Algorithm;

final class Crc32 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'crc32';
    }
}
