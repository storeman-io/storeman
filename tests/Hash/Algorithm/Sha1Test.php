<?php

namespace Storeman\Test\Hash\Algorithm;

use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Hash\Algorithm\Sha1;

class Sha1Test extends AbstractAlgorithmTest
{
    protected function getTestCases(): array
    {
        return [
            'Hello World' => '0a4d55a8d778e5022fab701977c5d840bbc486d0',
        ];
    }

    protected function getFunction(): HashAlgorithmInterface
    {
        return new Sha1();
    }
}
