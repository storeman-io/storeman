<?php

namespace Storeman\Test;

use Storeman\Container;
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

        $config = new Configuration();
        $config->setPath($testVault->getBasePath());
        $this->getTestVaultConfig($config)->setTitle('First');
        $this->getTestVaultConfig($config)->setTitle('Second');

        $storeman = new Storeman((new Container())->injectConfiguration($config));

        $this->assertCount(2, $storeman->buildOperationList());

        $operationResultList = $storeman->synchronize();

        $this->assertCount(2, $operationResultList);
    }

    protected function getTestVaultConfig(Configuration $configuration): VaultConfiguration
    {
        $config = new VaultConfiguration($configuration);
        $config->setAdapter('local');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
