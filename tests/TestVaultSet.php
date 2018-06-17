<?php

namespace Storeman\Test;

use Storeman\Index\Index;

class TestVaultSet
{
    /**
     * @var TestVault[]
     */
    protected $testVaults = [];

    public function __construct(int $count)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $this->testVaults[] = new TestVault();
        }
    }

    public function getTestVault(int $index): TestVault
    {
        if (!isset($this->testVaults[$index]))
        {
            throw new \OutOfRangeException();
        }

        return $this->testVaults[$index];
    }

    public function getIndex(int $index): Index
    {
        return $this->getTestVault($index)->getIndex();
    }
}
