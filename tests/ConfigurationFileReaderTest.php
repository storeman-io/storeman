<?php

namespace Storeman\Test;

use Storeman\Configuration;
use Storeman\ConfigurationFileReader;
use Storeman\Exception\ConfigurationException;
use Storeman\VaultConfiguration;
use Storeman\Exception\Exception;
use PHPUnit\Framework\TestCase;

class ConfigurationFileReaderTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testCompleteConfig()
    {
        $configFilePath = $this->writeConfig(<<<JSON
{
    "path": "/some/path",
    "exclude": [
        "to/be/excluded"
    ],
    "identity": "some identity",
    "vaults": [
        {
            "title": "Test",
            "adapter": "local",
            "lockAdapter": "storage",
            "indexMerger": "standard",
            "conflictHandler": "preferLocal",
            "operationListBuilder": "standard",
            "settings": {
                "path": "/another/path"
            }
        }
    ]
}
JSON
        );

        $reader = new ConfigurationFileReader();
        $config = $reader->getConfiguration($configFilePath);

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals('/some/path/', $config->getPath());
        $this->assertEquals(['to/be/excluded'], $config->getExclude());
        $this->assertEquals('some identity', $config->getIdentity());

        $vaultConfig = $config->getVaultByTitle('Test');

        $this->assertInstanceOf(VaultConfiguration::class, $vaultConfig);
        $this->assertEquals('local', $vaultConfig->getAdapter());
        $this->assertEquals('storage', $vaultConfig->getLockAdapter());
        $this->assertEquals('standard', $vaultConfig->getIndexMerger());
        $this->assertEquals('preferLocal', $vaultConfig->getConflictHandler());
        $this->assertEquals('standard', $vaultConfig->getOperationListBuilder());
        $this->assertEquals('/another/path', $vaultConfig->getSetting('path'));
        $this->assertEquals(['path' => '/another/path'], $vaultConfig->getSettings());
    }

    public function testInvalidFile()
    {
        $this->expectException(Exception::class);

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration('non-existent');
    }

    public function testInvalidJson()
    {
        $this->expectException(Exception::class);

        $configFilePath = $this->writeConfig('xxx');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    public function testMissingVaults()
    {
        $this->expectException(ConfigurationException::class);

        $configFilePath = $this->writeConfig('{}');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    public function testWrongVaultsType()
    {
        $this->expectException(ConfigurationException::class);

        $configFilePath = $this->writeConfig('{"vaults": "test"}');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    public function testMissingVaultAdapter()
    {
        $this->expectException(ConfigurationException::class);

        $configFilePath = $this->writeConfig('{"vaults": [{}]}');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    protected function writeConfig(string $json): string
    {
        $configFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        file_put_contents($configFilePath, $json);

        return $configFilePath;
    }
}
