<?php

namespace Storeman\Test;

use Storeman\Container;
use Storeman\FilesystemUtility;
use Storeman\Operation\OperationInterface;
use Storeman\OperationResult;
use Storeman\Storeman;
use Storeman\Config\Configuration;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\OperationResultList;
use Storeman\Vault;
use Storeman\Config\VaultConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class VaultTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

    public function testOnePartySynchronization()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $connectionTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $vault = $this->getLocalVault($testVault->getBasePath(), $connectionTarget);

        $this->assertIndexEqualsTestVault($testVault, $vault->getStoreman()->getLocalIndex());
        $this->assertNull($vault->getLastLocalIndex());
        $this->assertNull($vault->getRemoteIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->getMergedIndex());

        $this->assertSuccessfulOperations($vault->synchronize());

        $this->assertIndexEqualsTestVault($testVault, $vault->getStoreman()->getLocalIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->getLastLocalIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->getRemoteIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->getMergedIndex());
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

        $this->assertInstanceOf(Index::class, $firstVault->getRemoteIndex());
        $this->assertInstanceOf(Index::class, $firstVault->getLastLocalIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->getRemoteIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->getLastLocalIndex());
        $this->assertIndexContainsTestVault($firstTestVault, $secondVault->getRemoteIndex());

        $this->assertSuccessfulOperations($secondVault->synchronize());

        $this->assertInstanceOf(Index::class, $secondVault->getRemoteIndex());
        $this->assertInstanceOf(Index::class, $secondVault->getLastLocalIndex());
        $this->assertTrue($firstVault->getRemoteIndex()->equals($secondVault->getRemoteIndex()));
        $this->assertIndexContainsTestVault($firstTestVault, $firstVault->getRemoteIndex());
        $this->assertIndexContainsTestVault($secondTestVault, $firstVault->getRemoteIndex());

        $this->assertSuccessfulOperations($firstVault->synchronize());

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

        $this->assertIndexEqualsTestVault($testVault, $verificationVault->getStoreman()->getLocalIndex());
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

    private function assertIndexEqualsTestVault(TestVault $testVault, Index $index)
    {
        $this->assertTestVaultContainsIndex($testVault, $index);
        $this->assertIndexContainsTestVault($testVault, $index);
    }

    private function assertTestVaultContainsIndex(TestVault $testVault, Index $index)
    {
        foreach ($index as $indexObject)
        {
            $this->assertIndexObjectIsInTestVault($indexObject, $testVault);
        }
    }

    private function assertIndexContainsTestVault(TestVault $testVault, Index $index)
    {
        foreach ($testVault as $testVaultObject)
        {
            $this->assertTestVaultObjectIsInIndex($testVaultObject, $index);
        }
    }

    private function assertIndexObjectIsInTestVault(IndexObject $indexObject, TestVault $testVault)
    {
        $testVaultObject = $testVault->getObjectByRelativePath($indexObject->getRelativePath());

        $this->assertInstanceOf(SplFileInfo::class, $testVaultObject, sprintf('Failed to assert that object %s is part of the fiven test vault.', $indexObject->getRelativePath()));

        $this->assertTestVaultObjectEqualsIndexObject($testVaultObject, $indexObject);
    }

    private function assertTestVaultObjectIsInIndex(SplFileInfo $testVaultObject, Index $index)
    {
        $indexObject = $index->getObjectByPath($testVaultObject->getRelativePathname());

        $this->assertInstanceOf(IndexObject::class, $indexObject, sprintf('Failed to assert that object %s is part of the given index.', $testVaultObject->__toString()));

        $this->assertTestVaultObjectEqualsIndexObject($testVaultObject, $indexObject);
    }

    private function assertTestVaultObjectEqualsIndexObject(SplFileInfo $testVaultObject, IndexObject $indexObject)
    {
        $stat = FilesystemUtility::lstat($testVaultObject->getPathname());

        $this->assertEquals($testVaultObject->isFile(), $indexObject->isFile());
        $this->assertEquals($testVaultObject->isDir(), $indexObject->isDirectory());
        $this->assertEquals($testVaultObject->isLink(), $indexObject->isLink());
        $this->assertEquals($stat['mtime'], $indexObject->getMtime());
        $this->assertEquals($testVaultObject->getPerms() & 0777, $indexObject->getPermissions());

        if ($testVaultObject->isFile())
        {
            $this->assertEquals($testVaultObject->getSize(), $indexObject->getSize());
        }

        elseif ($testVaultObject->isLink())
        {
            $this->assertEquals($testVaultObject->getLinkTarget(), $indexObject->getLinkTarget());
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
