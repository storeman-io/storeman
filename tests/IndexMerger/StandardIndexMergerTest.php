<?php

namespace Storeman\Test\IndexMerger;

use Storeman\Config\Configuration;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PanickingConflictHandler;
use Storeman\FileReader;
use Storeman\Hash\Algorithm\Adler32;
use Storeman\Hash\Algorithm\Crc32;
use Storeman\Hash\Algorithm\Md5;
use Storeman\Hash\Algorithm\Sha1;
use Storeman\Hash\Algorithm\Sha256;
use Storeman\Hash\Algorithm\Sha512;
use Storeman\Hash\HashProvider;
use Storeman\Index\Index;
use Storeman\IndexMerger\StandardIndexMerger;
use Storeman\Test\TestVault;
use Storeman\Test\TestVaultSet;
use PHPUnit\Framework\TestCase;

class StandardIndexMergerTest extends TestCase
{
    public function testSimpleAdditionAndRemoval()
    {
        $testVault = new TestVault();
        $testVault->touch('file1');
        $testVault->touch('file2');

        $merger = $this->getIndexMerger($testVault);

        $firstState = $testVault->getIndex();

        $mergedIndex = $merger->merge(new PanickingConflictHandler(), new Index(), $firstState, null);

        $this->assertTrue($firstState->equals($mergedIndex));

        $testVault->remove('file1');
        $testVault->touch('file3');

        $actualIndex = $testVault->getIndex();
        $mergedIndex = $merger->merge(new PanickingConflictHandler(), $firstState, $actualIndex, $firstState);

        $this->assertTrue($mergedIndex->equals($actualIndex));
    }

    public function testNewClientMerge()
    {
        $testVault1 = new TestVault();
        $testVault1->touch('file');

        $merger = $this->getIndexMerger($testVault1);
        $mergedIndex = $merger->merge(new PanickingConflictHandler(), new Index(), $testVault1->getIndex(), null);

        $this->assertEquals(1, $mergedIndex->count());
        $this->assertTrue($testVault1->getIndex()->getObjectByPath('file')->equals($mergedIndex->getObjectByPath('file')));
    }

    public function testTrivialMerge()
    {
        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('fileA');
        $testVaultSet->getTestVault(1)->touch('fileB');

        $merger = $this->getIndexMerger($testVaultSet->getTestVault(0));
        $mergedIndex = $merger->merge(new PanickingConflictHandler(), $testVaultSet->getIndex(0), $testVaultSet->getIndex(1), null);

        $this->assertEquals(2, $mergedIndex->count());
        $this->assertTrue($testVaultSet->getIndex(0)->getObjectByPath('fileA')->equals($mergedIndex->getObjectByPath('fileA')));
        $this->assertTrue($testVaultSet->getIndex(1)->getObjectByPath('fileB')->equals($mergedIndex->getObjectByPath('fileB')));
    }

    public function testLocalChangeMerging()
    {
        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $merger = $this->getIndexMerger($testVaultSet->getTestVault(0));
        $mergedIndex = $merger->merge(new PanickingConflictHandler(), $remoteIndex, $localIndex, $remoteIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($localIndex->getObjectByPath('file')));
    }

    public function testRemoteChangeMerging()
    {
        $testVaultSet = new TestVaultSet(2);
        $testVaultSet->getTestVault(0)->touch('file', time() - 10);
        $testVaultSet->getTestVault(1)->touch('file', time() - 5);

        $localIndex = $testVaultSet->getIndex(0);
        $remoteIndex = $testVaultSet->getIndex(1);

        $merger = $this->getIndexMerger($testVaultSet->getTestVault(0));
        $mergedIndex = $merger->merge(new PanickingConflictHandler(), $remoteIndex, $localIndex, $localIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($remoteIndex->getObjectByPath('file')));
    }

    public function testConflictHandlingLocalUsage()
    {
        $time = time();

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

        $merger = $this->getIndexMerger($testVaultSet->getTestVault(0));
        $mergedIndex = $merger->merge($conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($localIndex->getObjectByPath('file')));
    }

    public function testConflictHandlingRemoteUsage()
    {
        $time = time();

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

        $merger = $this->getIndexMerger($testVaultSet->getTestVault(0));
        $mergedIndex = $merger->merge($conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);

        $this->assertTrue($mergedIndex->getObjectByPath('file')->equals($remoteIndex->getObjectByPath('file')));
    }

    protected function getIndexMerger(TestVault $testVault): StandardIndexMerger
    {
        $configuration = new Configuration();
        $configuration->setPath($testVault->getBasePath());

        $algorithms = [
            'adler32' => new Adler32(),
            'crc32' => new Crc32(),
            'md5' => new Md5(),
            'sha1' => new Sha1(),
            'sha256' => new Sha256(),
            'sha512' => new Sha512(),
        ];
        $fileReader = new FileReader($configuration, $algorithms);
        $hashProvider = new HashProvider($fileReader, $configuration, $algorithms);

        return new StandardIndexMerger($configuration, $hashProvider);
    }
}
