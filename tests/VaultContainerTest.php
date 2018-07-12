<?php

namespace Storeman\Test;

use Interop\Container\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Storeman\Config\Configuration;
use Storeman\Config\ConfigurationException;
use Storeman\Config\VaultConfiguration;
use Storeman\Container;
use Storeman\Storeman;
use Storeman\Vault;
use Storeman\VaultContainer;

class VaultContainerTest extends TestCase
{
    use ConfiguredMockProviderTrait;
    use TemporaryPathGeneratorProviderTrait;

    public function testAsBasicContainer()
    {
        $tmpPathProvider = $this->getTemporaryPathGenerator();

        $config = new Configuration();
        $config->addVault((new VaultConfiguration($config))->setTitle('a')->setAdapter('local')->setSetting('path', $tmpPathProvider->getTemporaryDirectory()));
        $config->addVault((new VaultConfiguration($config))->setTitle('b')->setAdapter('local')->setSetting('path', $tmpPathProvider->getTemporaryDirectory()));

        $vaultContainer = new VaultContainer($config, new Storeman(), new NullLogger());

        $vaultA = $vaultContainer->getVaultByTitle('a');
        $vaultB = $vaultContainer->getVaultByTitle('b');

        $this->assertInstanceOf(Vault::class, $vaultA);
        $this->assertEquals('a', $vaultA->getVaultConfiguration()->getTitle());

        $this->assertInstanceOf(Vault::class, $vaultB);
        $this->assertEquals('b', $vaultB->getVaultConfiguration()->getTitle());

        $this->assertFalse($vaultContainer->has('xxx'));
        $this->assertTrue($vaultContainer->has($vaultA->getHash()));
        $this->assertTrue($vaultContainer->has($vaultB->getHash()));

        $this->assertSame($vaultA, $vaultContainer->get($vaultA->getHash()));
        $this->assertSame($vaultB, $vaultContainer->get($vaultB->getHash()));
    }

    public function testNotFoundIsThrown()
    {
        $this->expectException(NotFoundException::class);

        $vaultContainer = new VaultContainer(new Configuration(), new Storeman(), new NullLogger());
        $vaultContainer->get('xxx');
    }

    public function testDuplicateHashDetection()
    {
        $path = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $config = new Configuration();
        $config->addVault((new VaultConfiguration($config))->setTitle('a')->setAdapter('local')->setSetting('path', $path));
        $config->addVault((new VaultConfiguration($config))->setTitle('b')->setAdapter('local')->setSetting('path', $path));

        $this->expectException(ConfigurationException::class);

        new VaultContainer($config, new Storeman(), new NullLogger());
    }

    public function testCorrectIterationOrder()
    {
        $tmpPathProvider = $this->getTemporaryPathGenerator();

        $pathA = $tmpPathProvider->getTemporaryDirectory('a');
        $pathB = $tmpPathProvider->getTemporaryDirectory('b');

        $config = new Configuration();
        $config->addVault((new VaultConfiguration($config))->setTitle('a')->setAdapter('local')->setSetting('path', $pathA));
        $config->addVault((new VaultConfiguration($config))->setTitle('b')->setAdapter('local')->setSetting('path', $pathB));

        $firstOrder = array_map(function(Vault $vault) { return $vault->getVaultConfiguration()->getTitle(); }, iterator_to_array(new VaultContainer($config, new Storeman((new Container())->injectConfiguration($config)), new NullLogger())));

        $config = new Configuration();
        $config->addVault((new VaultConfiguration($config))->setTitle('a')->setAdapter('local')->setSetting('path', $pathB));
        $config->addVault((new VaultConfiguration($config))->setTitle('b')->setAdapter('local')->setSetting('path', $pathA));

        $secondOrder = array_map(function(Vault $vault) { return $vault->getVaultConfiguration()->getTitle(); }, iterator_to_array(new VaultContainer($config, new Storeman((new Container())->injectConfiguration($config)), new NullLogger())));

        $this->assertEquals($firstOrder, array_reverse($secondOrder));
    }
}
