<?php

namespace Storeman\Hash\Algorithm;

final class Sha1 extends AbstractPhpHashAlgorithm
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sha1';
    }
}
