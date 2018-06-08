<?php

namespace Storeman\Test;

use Storeman\Storeman;
use Storeman\Configuration;
use Storeman\VaultConfiguration;
use PHPUnit\Framework\TestCase;

class StoremanTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testTwoWaySynchronization()
    {
        $testVault = new TestVault();
        $testVault->fwrite('test.ext', 'Hello World!');

        $config = new Configuration($testVault->getBasePath());
        $config->addVault($this->getTestVaultConfig()->setTitle('First'));
        $config->addVault($this->getTestVaultConfig()->setTitle('Second'));

        $storeman = new Storeman($config);

        $this->assertCount(2, $storeman->buildOperationList());

        $operationResultList = $storeman->synchronize();

        $this->assertCount(2, $operationResultList);
    }

    protected function getTestVaultConfig(): VaultConfiguration
    {
        $config = new VaultConfiguration('local');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
