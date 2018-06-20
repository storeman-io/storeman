<?php

namespace Storeman\Test\Hash\Algorithm;

use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Hash\Algorithm\Md5;

class Md5Test extends AbstractAlgorithmTest
{
    protected function getTestCases(): array
    {
        return [
            'Hello World' => 'b10a8db164e0754105b7a99be72e3fe5',
        ];
    }

    protected function getFunction(): HashAlgorithmInterface
    {
        return new Md5();
    }
}
