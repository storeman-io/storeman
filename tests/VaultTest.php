<?php

namespace Archivr\Test;

use Archivr\Connection\DummyConnection;
use Archivr\Connection\StreamConnection;
use Archivr\Index;
use Archivr\IndexObject;
use Archivr\OperationResultCollection;
use Archivr\Vault;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class VaultTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

    public function testBuildLocalIndex()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $vault = new Vault(new DummyConnection(), $testVault->getBasePath());

        $localIndex = $vault->buildLocalIndex();

        $this->assertInstanceOf(Index::class, $localIndex);
        $this->assertIndexEqualsTestVault($testVault, $localIndex);

        foreach ($testVault as $testVaultObject)
        {
            $this->assertTestVaultObjectIsInIndex($testVaultObject, $localIndex);
        }
    }

    public function testOnePartySynchronization()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $connectionTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $vault = new Vault(new StreamConnection($connectionTarget), $testVault->getBasePath());

        $this->assertIndexEqualsTestVault($testVault, $vault->buildLocalIndex());
        $this->assertNull($vault->loadLastLocalIndex());
        $this->assertNull($vault->loadRemoteIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->buildMergedIndex());

        $vault->synchronize();

        $this->assertIndexEqualsTestVault($testVault, $vault->buildLocalIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->loadLastLocalIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->loadRemoteIndex());
        $this->assertIndexEqualsTestVault($testVault, $vault->buildMergedIndex());
    }

    public function testTwoPartySynchronization()
    {
        // vaults are completely distinct
        $firstTestVault = $this->getTestVaultGenerator()->generate();
        $secondTestVault = $this->getTestVaultGenerator()->generate();

        $connectionTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $firstVault = new Vault(new StreamConnection($connectionTarget), $firstTestVault->getBasePath());
        $secondVault = new Vault(new StreamConnection($connectionTarget), $secondTestVault->getBasePath());

        $this->assertNull($firstVault->loadRemoteIndex());
        $this->assertNull($firstVault->loadLastLocalIndex());
        $this->assertNull($secondVault->loadRemoteIndex());
        $this->assertNull($secondVault->loadLastLocalIndex());

        $firstSynchronizationResult = $firstVault->synchronize();

        $this->assertInstanceOf(OperationResultCollection::class, $firstSynchronizationResult);
        $this->assertInstanceOf(Index::class, $firstVault->loadRemoteIndex());
        $this->assertInstanceOf(Index::class, $firstVault->loadLastLocalIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->loadRemoteIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->loadLastLocalIndex());

        $secondSynchronizationResult = $secondVault->synchronize();

        $this->assertInstanceOf(OperationResultCollection::class, $secondSynchronizationResult);
        $this->assertInstanceOf(Index::class, $secondVault->loadRemoteIndex());
        $this->assertInstanceOf(Index::class, $secondVault->loadLastLocalIndex());
        $this->assertTrue($firstVault->loadRemoteIndex()->equals($secondVault->loadRemoteIndex()));
        $this->assertIndexContainsTestVault($firstTestVault, $firstVault->loadRemoteIndex());
        $this->assertIndexContainsTestVault($secondTestVault, $firstVault->loadRemoteIndex());


        $thirdSynchronizationResult = $firstVault->synchronize();

        $this->assertInstanceOf(OperationResultCollection::class, $thirdSynchronizationResult);

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
        $this->assertEquals($testVaultObject->isFile(), $indexObject->isFile());
        $this->assertEquals($testVaultObject->isDir(), $indexObject->isDirectory());
        $this->assertEquals($testVaultObject->isLink(), $indexObject->isLink());
        $this->assertEquals($testVaultObject->getMTime(), $indexObject->getMtime());
        $this->assertEquals($testVaultObject->getPerms(), $indexObject->getMode());
    }
}