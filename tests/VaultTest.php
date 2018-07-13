<?php

namespace Storeman\Test;

use Storeman\Container;
use Storeman\Operation\OperationInterface;
use Storeman\OperationResult;
use Storeman\Storeman;
use Storeman\Config\Configuration;
use Storeman\Index\IndexObject;
use Storeman\OperationResultList;
use Storeman\Vault;
use Storeman\Config\VaultConfiguration;
use PHPUnit\Framework\TestCase;

class VaultTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

    public function testOnePartySynchronization()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $connectionTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $vault = $this->getLocalVault($testVault->getBasePath(), $connectionTarget);

        $this->assertTrue($testVault->getIndex()->equals($vault->getStoreman()->getLocalIndex()));
        $this->assertNull($vault->getLastLocalIndex());
        $this->assertNull($vault->getRemoteIndex());
        $this->assertTrue($testVault->getIndex()->equals($vault->getMergedIndex()));

        $this->assertSuccessfulOperations($vault->synchronize());

        $index = $testVault->getIndex();

        $this->assertTrue($index->equals($vault->getStoreman()->getLocalIndex()));
        $this->assertTrue($index->equals($vault->getLastLocalIndex(), IndexObject::CMP_IGNORE_BLOBID));
        $this->assertTrue($index->equals($vault->getRemoteIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
        $this->assertTrue($index->equals($vault->getMergedIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
    }

    public function testTwoPartySynchronization()
    {
        // vaults are completely distinct
        $firstTestVault = $this->getTestVaultGenerator()->generate();
        $secondTestVault = $this->getTestVaultGenerator()->generate();

        $connectionTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $firstVault = $this->getLocalVault($firstTestVault->getBasePath(), $connectionTarget);
        $secondVault = $this->getLocalVault($secondTestVault->getBasePath(), $connectionTarget);

        $this->assertNull($firstVault->getRemoteIndex());
        $this->assertNull($firstVault->getLastLocalIndex());
        $this->assertNull($secondVault->getRemoteIndex());
        $this->assertNull($secondVault->getLastLocalIndex());

        $this->assertSuccessfulOperations($firstVault->synchronize());

        $firstIndex = $firstTestVault->getIndex();

        $this->assertTrue($firstIndex->equals($firstVault->getRemoteIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
        $this->assertTrue($firstIndex->equals($firstVault->getLastLocalIndex(), IndexObject::CMP_IGNORE_BLOBID));
        $this->assertTrue($firstIndex->equals($secondVault->getRemoteIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));

        $this->assertSuccessfulOperations($secondVault->synchronize());

        $secondIndex = $secondTestVault->getIndex();

        $this->assertTrue($firstVault->getRemoteIndex()->equals($secondVault->getRemoteIndex()));
        $this->assertTrue($firstIndex->isSubsetOf($firstVault->getRemoteIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
        $this->assertTrue($secondIndex->isSubsetOf($firstVault->getRemoteIndex(), IndexObject::CMP_IGNORE_BLOBID | IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));

        $this->assertSuccessfulOperations($firstVault->synchronize());

        $this->assertTrue($firstVault->getStoreman()->getLocalIndex()->equals($secondVault->getStoreman()->getLocalIndex(), IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
    }

    public function testRestore()
    {
        $originalContent = md5(rand());

        $testVault = new TestVault();
        $testVault->fwrite('test.ext', $originalContent);

        $vault = $this->getLocalVault($testVault->getBasePath(), $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $this->assertSuccessfulOperations($vault->synchronize());

        $testVault->fwrite('test.ext', 'New Content');

        $this->assertSuccessfulOperations($vault->restore());

        $this->assertEquals($originalContent, file_get_contents($testVault->getBasePath() . 'test.ext'));
    }

    public function testDump()
    {
        $testVault = $this->getTestVaultGenerator()->generate();

        $vault = $this->getLocalVault($testVault->getBasePath(), $this->getTemporaryPathGenerator()->getTemporaryDirectory());
        $vault->synchronize();

        $dumpTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $this->assertSuccessfulOperations($vault->dump($dumpTarget));

        $verificationVault = $this->getLocalVault($dumpTarget, $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $this->assertTrue($testVault->getIndex()->equals($verificationVault->getStoreman()->getLocalIndex(), IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
    }

    protected function assertSuccessfulOperations(OperationResultList $operationResultList)
    {
        foreach ($operationResultList as $operationResult)
        {
            /** @var OperationResult $operationResult */

            $this->assertTrue($operationResult->isSuccess());
            $this->assertInstanceOf(OperationInterface::class, $operationResult->getOperation());
        }
    }

    private function getLocalVault(string $basePath, string $remotePath): Vault
    {
        $configuration = new Configuration();
        $configuration->setPath($basePath);

        $vaultConfiguration = new VaultConfiguration($configuration);
        $vaultConfiguration->setAdapter('local');
        $vaultConfiguration->setLockAdapter('storage');
        $vaultConfiguration->setSetting('path', $remotePath);

        $storeman = new Storeman((new Container())->injectConfiguration($configuration));

        return new Vault($storeman, $vaultConfiguration);
    }
}
