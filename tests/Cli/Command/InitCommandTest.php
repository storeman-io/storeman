<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\InitCommand;
use Storeman\Config\ConfigurationFileReader;
use Storeman\Storeman;
use Storeman\Test\TestVault;
use Storeman\Config\VaultConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends AbstractCommandTest
{
    public function testInitialization()
    {
        $testVault = new TestVault();

        $tester = new CommandTester(new InitCommand());
        $tester->setInputs([
            '', // local path
            get_current_user(), // identity
            'exclud.ed', '', // excluded paths
            'standard', // index builder

            // vaults
            'local', // storage driver
            'My Title', // title
            'amberjack', // vault layout
            'storage', // lock adapter
            'standard', // index merger
            'panicking', // conflict handler
            'standard', // operation list builder
            'foo', 'bar', '', // additional settings

            '', // no other vault
            'yes', // continue
        ]);

        $filePath = $testVault->getBasePath() . Storeman::CONFIG_FILE_NAME;
        $returnCode = $tester->execute([
            '-c' => $filePath,
        ]);

        $this->assertEquals(0, $returnCode);
        $this->assertTrue(file_exists($filePath) && is_readable($filePath));

        $configFileReader = new ConfigurationFileReader();
        $config = $configFileReader->getConfiguration($filePath);

        $this->assertEquals(get_current_user(), $config->getIdentity());
        $this->assertEquals(['exclud.ed'], $config->getExclude());
        $this->assertEquals('standard', $config->getIndexBuilder());

        $this->assertCount(1, $config->getVaults());

        $vault = $config->getVaultByTitle('My Title');

        $this->assertInstanceOf(VaultConfiguration::class, $vault);

        $this->assertEquals('My Title', $vault->getTitle());
        $this->assertEquals('local', $vault->getAdapter());
        $this->assertEquals('amberjack', $vault->getVaultLayout());
        $this->assertEquals('storage', $vault->getLockAdapter());
        $this->assertEquals('standard', $vault->getIndexMerger());
        $this->assertEquals('panicking', $vault->getConflictHandler());
        $this->assertEquals('standard', $vault->getOperationListBuilder());
        $this->assertEquals(['foo' => 'bar'], $vault->getSettings());
    }

    public function testCallOutsideArchive(array $input = [])
    {
        // for this command this is not an error case

        $this->assertTrue(true);
    }

    protected function getCommand(): Command
    {
        return new InitCommand();
    }
}
