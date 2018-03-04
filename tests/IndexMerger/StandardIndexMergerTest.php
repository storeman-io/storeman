<?php

namespace Archivr\Test\IndexMerger;

use Archivr\Index;
use Archivr\IndexMerger\StandardIndexMerger;
use Archivr\Test\TestVault;
use Archivr\Test\TestVaultSet;
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

        $mergedIndex = $merger->merge(new Index(), $firstState);

        $this->assertTrue($firstState->equals($mergedIndex));

        $testVault->remove('file1');
        $testVault->touch('file3');

        $mergedIndex = $merger->merge($firstState, $testVault->getIndex(), $firstState);
        $actualIndex = $testVault->getIndex();

        $this->assertTrue($mergedIndex->equals($actualIndex));
    }

    public function testNewClientMerge()
    {
        $testVault1 = new TestVault();
        $testVault1->touch('file');

        $merger = new StandardIndexMerger();

        $mergedIndex = $merger->merge(new Index(), $testVault1->getIndex());

        $this->assertEquals(1, $mergedIndex->count());
        $this->assertTrue($testVault1->getIndex()->getObjectByPath('file')->equals($mergedIndex->getObjectByPath('file')));
    }

    public function testTrivialMerge()
    {
        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('fileA');
        $testVaultSet->getTestVault(1)->touch('fileB');

        $merger = new StandardIndexMerger();

        $mergedIndex = $merger->merge($testVaultSet->getIndex(0), $testVaultSet->getIndex(1));

        $this->assertEquals(2, $mergedIndex->count());
        $this->assertTrue($mergedIndex->getObjectByPath('fileA')->equals($testVaultSet->getIndex(0)->getObjectByPath('fileA')));
        $this->assertTrue($mergedIndex->getObjectByPath('fileB')->equals($testVaultSet->getIndex(1)->getObjectByPath('fileB')));
    }

    public function testLocalChangeMerging()
    {
        $merger = new StandardIndexMerger();

        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $mergedIndex = $merger->merge($remoteIndex, $localIndex, $remoteIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($localIndex->getObjectByPath('file')));
    }

    public function testRemoteChangeMerging()
    {
        $merger = new StandardIndexMerger();

        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $mergedIndex = $merger->merge($remoteIndex, $localIndex, $localIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($remoteIndex->getObjectByPath('file')));
    }
}
