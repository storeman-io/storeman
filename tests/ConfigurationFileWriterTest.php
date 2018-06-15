<?php

namespace Storeman\Test;

use PHPUnit\Framework\TestCase;
use Storeman\Configuration;
use Storeman\ConfigurationFileWriter;
use Storeman\VaultConfiguration;

class ConfigurationFileWriterTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function test()
    {
        $configuration = new Configuration();
        $configuration->setPath('/some/path/');
        $configuration->setExclude(['to*', '*be', '*excluded*']);
        $configuration->setIdentity('My Identity');

        $vaultConfiguration = new VaultConfiguration($configuration);
        $vaultConfiguration->setTitle('Some Title');
        $vaultConfiguration->setVaultLayout('myVaultLayout');
        $vaultConfiguration->setAdapter('myAdapter');
        $vaultConfiguration->setLockAdapter('myLockAdapter');
        $vaultConfiguration->setConflictHandler('myConflictHandler');
        $vaultConfiguration->setIndexMerger('myIndexMerger');
        $vaultConfiguration->setOperationListBuilder('myOperationListBuilder');
        $vaultConfiguration->setSettings([
            'foo' => 'bar',
            'x' => 123
        ]);


        $path = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $writer = new ConfigurationFileWriter();
        $writer->writeConfigurationFile($configuration, $path, false);

        $output = $writer->buildConfigurationFile($configuration, false);
        $written = file_get_contents($path);

        $this->assertEquals($output, $written);
        $this->assertEquals($output, $writer->buildConfigurationFile($configuration, true));


        $array = json_decode($output, true);

        $this->assertEquals($configuration->getArrayCopy(), $array);

        $this->assertEquals($configuration->getPath(), $array['path']);
        $this->assertEquals($configuration->getExclude(), $array['exclude']);
        $this->assertEquals($configuration->getIdentity(), $array['identity']);

        $this->assertCount(1, $array['vaults']);
        $this->assertTrue(isset($array['vaults'][0]));

        $vault = $array['vaults'][0];
        $this->assertEquals($vaultConfiguration->getTitle(), $vault['title']);
        $this->assertEquals($vaultConfiguration->getVaultLayout(), $vault['vaultLayout']);
        $this->assertEquals($vaultConfiguration->getAdapter(), $vault['adapter']);
        $this->assertEquals($vaultConfiguration->getLockAdapter(), $vault['lockAdapter']);
        $this->assertEquals($vaultConfiguration->getConflictHandler(), $vault['conflictHandler']);
        $this->assertEquals($vaultConfiguration->getIndexMerger(), $vault['indexMerger']);
        $this->assertEquals($vaultConfiguration->getOperationListBuilder(), $vault['operationListBuilder']);
        $this->assertEquals($vaultConfiguration->getSettings(), $vault['settings']);
    }
}
