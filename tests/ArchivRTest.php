<?php

namespace Archivr\Test;

use Archivr\ArchivR;
use Archivr\Configuration;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;
use PHPUnit\Framework\TestCase;

class ArchivRTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testTwoWaySynchronization()
    {
        $testVault = new TestVault();
        $testVault->fwrite('test.ext', 'Hello World!');

        $config = new Configuration();
        $config->setLocalPath($testVault->getBasePath());
        $config->addVaultConfiguration($this->getTestVaultConfig()->setTitle('First'));
        $config->addVaultConfiguration($this->getTestVaultConfig()->setTitle('Second'));

        $archivr = new ArchivR($config);

        $this->assertCount(2, $archivr->buildOperationList());

        $operationResultList = $archivr->synchronize();

        $this->assertCount(2, $operationResultList);
    }

    public function testInvalidConnectionRetrieval()
    {
        $this->expectException(Exception::class);

        $archivr = new ArchivR(new Configuration());

        $this->assertNull($archivr->getStorageDriver('x'));
    }

    public function testInvalidLockAdapterRetrieval()
    {
        $this->expectException(Exception::class);

        $archivr = new ArchivR(new Configuration());

        $this->assertNull($archivr->getLockAdapter('x'));
    }

    protected function getTestVaultConfig(): VaultConfiguration
    {
        $config = new VaultConfiguration('path', 'connection');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
