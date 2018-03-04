<?php

namespace Archivr\Test;

use Archivr\StorageDriver\DummyStorageDriver;
use Archivr\StorageDriver\FlysystemStorageDriver;
use Archivr\Index;
use Archivr\IndexMerger\IndexMergerInterface;
use Archivr\IndexMerger\StandardIndexMerger;
use Archivr\IndexObject;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use Archivr\OperationResultList;
use Archivr\Vault;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class VaultTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

    public function testIndexMergerInjection()
    {
        $vault = new Vault('test', $this->getTemporaryPathGenerator()->getTemporaryDirectory(), new DummyStorageDriver());

        $this->assertEquals(StandardIndexMerger::class, get_class($vault->getIndexMerger()));

        $newIndexMerger = $this->createMock(IndexMergerInterface::class);
        $vault->setIndexMerger($newIndexMerger);

        $this->assertEquals($newIndexMerger, $vault->getIndexMerger());
    }

    public function testLockAdapterInjection()
    {
        $vault = new Vault('test', $this->getTemporaryPathGenerator()->getTemporaryDirectory(), new DummyStorageDriver());

        $this->assertEquals(ConnectionBasedLockAdapter::class, get_class($vault->getLockAdapter()));

        $newLockAdapter = $this->createMock(LockAdapterInterface::class);
        $vault->setLockAdapter($newLockAdapter);

        $this->assertEquals($newLockAdapter, $vault->getLockAdapter());
    }

    public function testBuildLocalIndex()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $vault = new Vault('test', $testVault->getBasePath(), new DummyStorageDriver());

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
        $vault = $this->getLocalVault($testVault->getBasePath(), $connectionTarget);

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

        $firstVault = $this->getLocalVault($firstTestVault->getBasePath(), $connectionTarget);
        $secondVault = $this->getLocalVault($secondTestVault->getBasePath(), $connectionTarget);

        $this->assertNull($firstVault->loadRemoteIndex());
        $this->assertNull($firstVault->loadLastLocalIndex());
        $this->assertNull($secondVault->loadRemoteIndex());
        $this->assertNull($secondVault->loadLastLocalIndex());

        $firstSynchronizationResult = $firstVault->synchronize();

        $this->assertInstanceOf(OperationResultList::class, $firstSynchronizationResult);
        $this->assertInstanceOf(Index::class, $firstVault->loadRemoteIndex());
        $this->assertInstanceOf(Index::class, $firstVault->loadLastLocalIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->loadRemoteIndex());
        $this->assertIndexEqualsTestVault($firstTestVault, $firstVault->loadLastLocalIndex());
        $this->assertIndexContainsTestVault($firstTestVault, $secondVault->loadRemoteIndex());

        $secondSynchronizationResult = $secondVault->synchronize();

        $this->assertInstanceOf(OperationResultList::class, $secondSynchronizationResult);
        $this->assertInstanceOf(Index::class, $secondVault->loadRemoteIndex());
        $this->assertInstanceOf(Index::class, $secondVault->loadLastLocalIndex());
        $this->assertTrue($firstVault->loadRemoteIndex()->equals($secondVault->loadRemoteIndex()));
        $this->assertIndexContainsTestVault($firstTestVault, $firstVault->loadRemoteIndex());
        $this->assertIndexContainsTestVault($secondTestVault, $firstVault->loadRemoteIndex());


        $thirdSynchronizationResult = $firstVault->synchronize();

        $this->assertInstanceOf(OperationResultList::class, $thirdSynchronizationResult);

    }

    public function testRestore()
    {
        $originalContent = md5(rand());

        $testVault = new TestVault();
        $testVault->fwrite('test.ext', $originalContent);

        $vault = $this->getLocalVault($testVault->getBasePath(), $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $vault->synchronize();

        $testVault->fwrite('test.ext', 'New Content');

        $vault->restore();

        $this->assertEquals($originalContent, file_get_contents($testVault->getBasePath() . 'test.ext'));
    }

    public function testDump()
    {
        $testVault = $this->getTestVaultGenerator()->generate();

        $vault = $this->getLocalVault($testVault->getBasePath(), $this->getTemporaryPathGenerator()->getTemporaryDirectory());
        $vault->synchronize();

        $dumpTarget = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $vault->dump($dumpTarget);

        $verificationVault = $this->getLocalVault($dumpTarget, $this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $this->assertIndexEqualsTestVault($testVault, $verificationVault->buildLocalIndex());
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
        return new Vault('test', $basePath, new FlysystemStorageDriver(new Filesystem(new Local($remotePath))));
    }
}
