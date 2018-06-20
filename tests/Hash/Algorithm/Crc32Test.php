<?php

namespace Storeman\Test\Hash\Algorithm;

use Storeman\Hash\Algorithm\Crc32;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;

class Crc32Test extends AbstractAlgorithmTest
{
    protected function getTestCases(): array
    {
        return [
            'Hello World' => 'da895c06',
        ];
    }

    protected function getFunction(): HashAlgorithmInterface
    {
        return new Crc32();
    }
}
