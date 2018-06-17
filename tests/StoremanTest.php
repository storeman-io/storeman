<?php

namespace Storeman\Test;

use Storeman\Container;
use Storeman\Storeman;
use Storeman\Config\Configuration;
use Storeman\Config\VaultConfiguration;
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
        $vaultContainer = $storeman->getVaultContainer();

        $this->assertCount(2, $vaultContainer);
        $this->assertCount(2, $vaultContainer->getVaultsByTitles(['First', 'Second']));
        $this->assertCount(0, $vaultContainer->get('First')->getVaultLayout()->getSynchronizations());
        $this->assertCount(0, $vaultContainer->get('Second')->getVaultLayout()->getSynchronizations());

        $operationResultList = $storeman->synchronize();

        $this->assertCount(2, $operationResultList);

        $this->assertCount(1, $vaultContainer->get('First')->getVaultLayout()->getSynchronizations());
        $this->assertCount(1, $vaultContainer->get('Second')->getVaultLayout()->getSynchronizations());
    }

    protected function getTestVaultConfig(Configuration $configuration): VaultConfiguration
    {
        $config = new VaultConfiguration($configuration);
        $config->setAdapter('local');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
