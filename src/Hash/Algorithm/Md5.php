<?php

namespace Storeman\Hash\Algorithm;

final class Md5 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'md5';
    }
}
