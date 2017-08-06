<?php

namespace Archivr\Test;

use Archivr\Configuration;
use Archivr\ConfigurationFileReader;
use Archivr\ConnectionConfiguration;
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
    "vaults": [
        {
            "title": "Test",
            "adapter": "path",
            "lockAdapter": "connection",
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
        $this->assertEquals('/some/path', $config->getLocalPath());
        $this->assertEquals(['to/be/excluded'], $config->getExclusions());

        $connectionConfig = $config->getConnectionConfigurationByTitle('Test');

        $this->assertInstanceOf(ConnectionConfiguration::class, $connectionConfig);
        $this->assertEquals('path', $connectionConfig->getVaultAdapter());
        $this->assertEquals('connection', $connectionConfig->getLockAdapter());
        $this->assertEquals('/another/path', $connectionConfig->getSetting('path'));
        $this->assertEquals(['path' => '/another/path'], $connectionConfig->getSettings());
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
