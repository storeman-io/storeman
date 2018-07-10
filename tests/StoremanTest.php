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
        $this->assertCount(0, $vaultContainer->getVaultByTitle('First')->getVaultLayout()->getSynchronizations());
        $this->assertCount(0, $vaultContainer->getVaultByTitle('Second')->getVaultLayout()->getSynchronizations());

        $operationResultList = $storeman->synchronize();

        $this->assertCount(2, $operationResultList);

        $this->assertCount(1, $vaultContainer->getVaultByTitle('First')->getVaultLayout()->getSynchronizations());
        $this->assertCount(1, $vaultContainer->getVaultByTitle('Second')->getVaultLayout()->getSynchronizations());
    }

    public function testPotentiallyAmbivalentPathExclusion()
    {
        $testVault = new TestVault();
        $testVault->touch('x' . Storeman::CONFIG_FILE_NAME);
        $testVault->touch('x' . Storeman::METADATA_DIRECTORY_NAME);
        $testVault->touch(Storeman::CONFIG_FILE_NAME . 'x');
        $testVault->touch(Storeman::METADATA_DIRECTORY_NAME . 'x');

        $config = new Configuration();
        $config->setPath($testVault->getBasePath());
        $this->getTestVaultConfig($config)->setTitle('test');

        $storeman = new Storeman((new Container())->injectConfiguration($config));
        $storeman->getMetadataDirectoryPath(); // init dir
        $index = $storeman->getLocalIndex();

        $this->assertCount(4, $index);
    }

    protected function getTestVaultConfig(Configuration $configuration): VaultConfiguration
    {
        $config = new VaultConfiguration($configuration);
        $config->setAdapter('local');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
