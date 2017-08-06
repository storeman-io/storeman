<?php

namespace Archivr\Test;

use Archivr\ArchivR;
use Archivr\Configuration;
use Archivr\ConnectionConfiguration;
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
        $config->addConnectionConfiguration($this->getTestVaultConfig()->setTitle('First'));
        $config->addConnectionConfiguration($this->getTestVaultConfig()->setTitle('Second'));

        $archivr = new ArchivR($config);

        $this->assertCount(2, $archivr->buildOperationCollection());

        $operationResultCollection = $archivr->synchronize();

        $this->assertCount(2, $operationResultCollection);
    }

    public function testInvalidConnectionRetrieval()
    {
        $this->expectException(Exception::class);

        $archivr = new ArchivR(new Configuration());

        $this->assertNull($archivr->getConnection('x'));
    }

    public function testInvalidLockAdapterRetrieval()
    {
        $this->expectException(Exception::class);

        $archivr = new ArchivR(new Configuration());

        $this->assertNull($archivr->getLockAdapter('x'));
    }

    protected function getTestVaultConfig(): ConnectionConfiguration
    {
        $config = new ConnectionConfiguration('path', 'connection');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
