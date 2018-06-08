<?php

namespace Archivr\Test;

use Archivr\ArchivR;
use Archivr\Configuration;
use Archivr\VaultConfiguration;
use PHPUnit\Framework\TestCase;

class ArchivRTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testTwoWaySynchronization()
    {
        $testVault = new TestVault();
        $testVault->fwrite('test.ext', 'Hello World!');

        $config = new Configuration($testVault->getBasePath());
        $config->addVault($this->getTestVaultConfig()->setTitle('First'));
        $config->addVault($this->getTestVaultConfig()->setTitle('Second'));

        $archivr = new ArchivR($config);

        $this->assertCount(2, $archivr->buildOperationList());

        $operationResultList = $archivr->synchronize();

        $this->assertCount(2, $operationResultList);
    }

    protected function getTestVaultConfig(): VaultConfiguration
    {
        $config = new VaultConfiguration('local');
        $config->setSetting('path', $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        return $config;
    }
}
