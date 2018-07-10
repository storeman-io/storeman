<?php

namespace Storeman\Test;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Storeman\Config\Configuration;
use Storeman\Config\VaultConfiguration;
use Storeman\Container;
use Storeman\Storeman;
use Storeman\Vault;
use Storeman\VaultContainer;

class VaultContainerTest extends TestCase
{
    use ConfiguredMockProviderTrait;
    use TemporaryPathGeneratorProviderTrait;

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
