<?php

namespace Storeman\Test\IndexMerger;

use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\Index;
use Storeman\IndexMerger\StandardIndexMerger;
use Storeman\Test\TestVault;
use Storeman\Test\TestVaultSet;
use PHPUnit\Framework\TestCase;

class StandardIndexMergerTest extends TestCase
{
    public function testSimpleAdditionAndRemoval()
    {
        $merger = new StandardIndexMerger();

        $testVault = new TestVault();
        $testVault->touch('file1');
        $testVault->touch('file2');

        $firstState = $testVault->getIndex();

        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), new Index(), $firstState);

        $this->assertTrue($firstState->equals($mergedIndex));

        $testVault->remove('file1');
        $testVault->touch('file3');

        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), $firstState, $testVault->getIndex(), $firstState);
        $actualIndex = $testVault->getIndex();

        $this->assertTrue($mergedIndex->equals($actualIndex));
    }

    public function testNewClientMerge()
    {
        $testVault1 = new TestVault();
        $testVault1->touch('file');

        $merger = new StandardIndexMerger();
        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), new Index(), $testVault1->getIndex());

        $this->assertEquals(1, $mergedIndex->count());
        $this->assertTrue($testVault1->getIndex()->getObjectByPath('file') == $mergedIndex->getObjectByPath('file'));
    }

    public function testTrivialMerge()
    {
        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('fileA');
        $testVaultSet->getTestVault(1)->touch('fileB');

        $merger = new StandardIndexMerger();
        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), $testVaultSet->getIndex(0), $testVaultSet->getIndex(1));

        $this->assertEquals(2, $mergedIndex->count());
        $this->assertTrue($mergedIndex->getObjectByPath('fileA') == $testVaultSet->getIndex(0)->getObjectByPath('fileA'));
        $this->assertTrue($mergedIndex->getObjectByPath('fileB') == $testVaultSet->getIndex(1)->getObjectByPath('fileB'));
    }

    public function testLocalChangeMerging()
    {
        $merger = new StandardIndexMerger();

        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), $remoteIndex, $localIndex, $remoteIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file') == $localIndex->getObjectByPath('file'));
    }

    public function testRemoteChangeMerging()
    {
        $merger = new StandardIndexMerger();

        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $mergedIndex = $merger->merge($this->getConflictHandlerMock(), $remoteIndex, $localIndex, $localIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file') == $remoteIndex->getObjectByPath('file'));
    }

    public function testConflictHandlingLocalUsage()
    {
        $time = time();
        $merger = new StandardIndexMerger();


        $testVaultSet = new TestVaultSet(3);
        $testVaultSet->getTestVault(0)->touch('file', $time - 10);
        $testVaultSet->getTestVault(1)->touch('file', $time - 5);
        $testVaultSet->getTestVault(2)->touch('file', $time - 3);

        $lastLocalIndex = $testVaultSet->getIndex(0);
        $localIndex =  $testVaultSet->getIndex(1);
        $remoteIndex = $testVaultSet->getIndex(2);

        $conflictHandler = $this->createMock(ConflictHandlerInterface::class);
        $conflictHandler
            ->expects($this->once())
            ->method('handleConflict')
            ->willReturn(ConflictHandlerInterface::USE_LOCAL);

        /** @var ConflictHandlerInterface $conflictHandler */

        $mergedIndex = $merger->merge($conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file') == $localIndex->getObjectByPath('file'));
    }

    public function testConflictHandlingRemoteUsage()
    {
        $time = time();
        $merger = new StandardIndexMerger();


        $testVaultSet = new TestVaultSet(3);
        $testVaultSet->getTestVault(0)->touch('file', $time - 10);
        $testVaultSet->getTestVault(1)->touch('file', $time - 3);
        $testVaultSet->getTestVault(2)->touch('file', $time - 5);

        $lastLocalIndex = $testVaultSet->getIndex(0);
        $localIndex =  $testVaultSet->getIndex(1);
        $remoteIndex = $testVaultSet->getIndex(2);

        $conflictHandler = $this->createMock(ConflictHandlerInterface::class);
        $conflictHandler
            ->expects($this->once())
            ->method('handleConflict')
            ->willReturn(ConflictHandlerInterface::USE_REMOTE);

        /** @var ConflictHandlerInterface $conflictHandler */

        $mergedIndex = $merger->merge($conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file') == $remoteIndex->getObjectByPath('file'));
    }

    protected function getConflictHandlerMock(): ConflictHandlerInterface
    {
        return $this->createMock(ConflictHandlerInterface::class);
    }
}
