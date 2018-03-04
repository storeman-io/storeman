<?php

namespace Archivr\Test;

use Archivr\Configuration;
use Archivr\ConfigurationFileReader;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;
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
            "storage": "local",
            "lockAdapter": "storage",
            "indexMerger": "standard",
            "conflictHandler": "preferLocal",
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
        $this->assertEquals('/some/path/', $config->getLocalPath());
        $this->assertEquals(['to/be/excluded'], $config->getExclusions());
        $this->assertEquals('some identity', $config->getIdentity());

        $vaultConfig = $config->getVaultConfigurationByTitle('Test');

        $this->assertInstanceOf(VaultConfiguration::class, $vaultConfig);
        $this->assertEquals('local', $vaultConfig->getStorageDriver());
        $this->assertEquals('storage', $vaultConfig->getLockAdapter());
        $this->assertEquals('standard', $vaultConfig->getIndexMerger());
        $this->assertEquals('preferLocal', $vaultConfig->getConflictHandler());
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
        $this->expectException(Exception::class);

        $configFilePath = $this->writeConfig('{}');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    public function testWrongVaultsType()
    {
        $this->expectException(Exception::class);

        $configFilePath = $this->writeConfig('{"vaults": "test"}');

        $reader = new ConfigurationFileReader();
        $reader->getConfiguration($configFilePath);
    }

    public function testMissingVaultAdapter()
    {
        $this->expectException(Exception::class);

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
