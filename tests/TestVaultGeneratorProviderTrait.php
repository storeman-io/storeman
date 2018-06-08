<?php

namespace Storeman\Test;

trait TestVaultGeneratorProviderTrait
{
    /**
     * @var TestVaultGenerator
     */
    private $testVaultGenerator;

    private function getTestVaultGenerator(): TestVaultGenerator
    {
        if ($this->testVaultGenerator === null)
        {
            $this->testVaultGenerator = new TestVaultGenerator();
        }

        return $this->testVaultGenerator;
    }
}
