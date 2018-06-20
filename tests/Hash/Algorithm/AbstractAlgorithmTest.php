<?php

namespace Storeman\Test\Hash\Algorithm;

use PHPUnit\Framework\TestCase;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;

abstract class AbstractAlgorithmTest extends TestCase
{
    public function test()
    {
        $function = $this->getFunction();

        foreach ($this->getTestCases() as $plainText => $hash)
        {
            $function->initialize();

            $chunks = explode($this->getTestCasePlainTextDelimiter(), $plainText);

            foreach ($chunks as $index => $chunk)
            {
                if (array_key_exists($index + 1, $chunks))
                {
                    $chunk .= $this->getTestCasePlainTextDelimiter();
                }

                $function->digest($chunk);
            }

            $this->assertEquals($hash, $function->finalize());
        }
    }

    protected function getTestCasePlainTextDelimiter(): string
    {
        return ' ';
    }

    abstract protected function getTestCases(): array;
    abstract protected function getFunction(): HashAlgorithmInterface;
}
