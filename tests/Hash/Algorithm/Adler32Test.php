<?php

namespace Storeman\Test\Hash\Algorithm;

use Storeman\Hash\Algorithm\Adler32;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;

class Adler32Test extends AbstractAlgorithmTest
{
    protected function getTestCases(): array
    {
        return [
            'Hello World' => '180b041d',
        ];
    }

    protected function getFunction(): HashAlgorithmInterface
    {
        return new Adler32();
    }
}
