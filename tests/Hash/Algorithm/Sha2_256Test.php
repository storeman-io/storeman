<?php

namespace Storeman\Test\Hash\Algorithm;

use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Hash\Algorithm\Sha2_256;

class Sha2_256Test extends AbstractAlgorithmTest
{
    protected function getTestCases(): array
    {
        return [
            'Hello World' => 'a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e',
        ];
    }

    protected function getFunction(): HashAlgorithmInterface
    {
        return new Sha2_256();
    }
}
