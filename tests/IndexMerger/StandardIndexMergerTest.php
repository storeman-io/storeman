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
use Storeman\Hash\Algorithm\Sha2_256;
use Storeman\Hash\Algorithm\Sha2_512;
use Storeman\Hash\HashProvider;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\IndexMerger\IndexMergerInterface;
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

        $this->assertTrue($mergedIndex->equals($actualIndex, IndexObject::CMP_IGNORE_INODE | IndexObject::CMP_IGNORE_CTIME));
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

        $this->assertEquals($localIndex->getObjectByPath('file')->getMtime(), $mergedIndex->getObjectByPath('file')->getMtime());
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

        $this->assertEquals($remoteIndex->getObjectByPath('file')->getMtime(), $mergedIndex->getObjectByPath('file')->getMtime());
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

        $this->assertEquals($localIndex->getObjectByPath('file')->getMtime(), $mergedIndex->getObjectByPath('file')->getMtime());
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

        $this->assertEquals($remoteIndex->getObjectByPath('file')->getMtime(), $mergedIndex->getObjectByPath('file')->getMtime());
    }

    public function testBlobIdReusage()
    {
        $testVault = new TestVault();
        $testVault->touch('file');

        $merger = $this->getIndexMerger($testVault);

        $localIndex = $testVault->getIndex();
        $remoteIndex = $testVault->getIndex();
        $remoteIndex->getObjectByPath('file')->setBlobId('xxx');

        $mergedIndex = $merger->merge(new PanickingConflictHandler(), $remoteIndex, $localIndex, $remoteIndex);

        $this->assertEquals('xxx', $mergedIndex->getObjectByPath('file')->getBlobId());
    }

    public function testContentModificationDetectionWithManipulatedMtime()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'foo');
        $testVault->touch('file.ext', time());

        $indexA = $testVault->getIndex();
        $objectA = $indexA->getObjectByPath('file.ext');
        $objectA->setBlobId('xxx');
        $objectA->getHashes()->addHash('sha2-256', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae');

        // obvious change as we can compare file size
        $testVault->fwrite('file.ext', 'obvious');
        $testVault->touch('file.ext', $objectA->getMtime());

        $mergedIndex = $this->getIndexMerger($testVault)->merge(new PanickingConflictHandler(), $indexA, $testVault->getIndex(), $indexA);
        $this->assertNull($mergedIndex->getObjectByPath('file.ext')->getBlobId());

        $testVault->fwrite('file.ext', 'bar');
        $testVault->touch('file.ext', $objectA->getMtime());

        $mergedIndex = $this->getIndexMerger($testVault)->merge(new PanickingConflictHandler(), $indexA, $testVault->getIndex(), $indexA);
        $this->assertNull($mergedIndex->getObjectByPath('file.ext')->getBlobId());
    }

    public function testCtimeIgnorance()
    {
        $testVault = new TestVault();
        $testVault->touch('file', time());

        $index = $testVault->getIndex();

        sleep(1);

        $object = $index->getObjectByPath('file');

        $object->setBlobId('xxx'); // make index object identifiable
        $testVault->touch('file', $object->getMtime()); // change only ctime

        $localIndex = $testVault->getIndex();

        $mergedIndex = $this->getIndexMerger($testVault)->merge(new PanickingConflictHandler(), $index, $localIndex, $index);

        $this->assertEquals('xxx', $mergedIndex->getObjectByPath('file')->getBlobId());
    }

    public function testNoIndexObjectReusage()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World');

        $index = $testVault->getIndex();
        $object = $index->getObjectByPath('file.ext');

        $merger = $this->getIndexMerger($testVault);

        $this->assertNotSame($object, $merger->merge(new PanickingConflictHandler(), $index, new Index(), null)->getObjectByPath('file.ext'));
        $this->assertNotSame($object, $merger->merge(new PanickingConflictHandler(), new Index(), $index, null)->getObjectByPath('file.ext'));
        $this->assertNotSame($object, $merger->merge(new PanickingConflictHandler(), $index, new Index(), $index)->getObjectByPath('file.ext'));
    }

    public function testBlobIdInjection()
    {
        $testVault = new TestVault();
        $testVault->touch('file');

        $localIndex = $testVault->getIndex();
        $remoteIndex = $testVault->getIndex();

        $remoteIndex->getObjectByPath('file')->setBlobId('xxx');

        $this->getIndexMerger($testVault)->merge(new PanickingConflictHandler(), $remoteIndex, $localIndex, $remoteIndex, IndexMergerInterface::INJECT_BLOBID);

        $this->assertEquals('xxx', $localIndex->getObjectByPath('file')->getBlobId());
    }

    public function testIndependentContentAndMetadataModificationHandling()
    {
        $localTestVault = new TestVault();
        $localTestVault->fwrite('file.ext', 'foo');
        $localTestVault->chmod('file.ext', 0644);
        $localTestVault->touch('file.ext', 1000);

        $lastLocalIndex = $localTestVault->getIndex();
        $lastLocalIndex->getObjectByPath('file.ext')->getHashes()->addHash('sha2-256', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae');

        $localTestVault->chmod('file.ext', 0777);

        $localIndex = $localTestVault->getIndex();
        $localIndex->getObjectByPath('file.ext')->getHashes()->addHash('sha2-256', '2c26b46b68ffc68ff99b453c1d30413413422d706483bfa0f98a5e886266e7ae');

        $remoteTestVault = new TestVault();
        $remoteTestVault->fwrite('file.ext', 'bar');
        $remoteTestVault->chmod('file.ext', 0644);
        $remoteTestVault->touch('file.ext', 1000);

        $remoteIndex = $remoteTestVault->getIndex();
        $remoteIndex->getObjectByPath('file.ext')->getHashes()->addHash('sha2-256', 'fcde2b2edba56bf408601fb721fe9b5c338d10ee429ea04fae5511b68fbf8fb9');

        $mergedIndex = $this->getIndexMerger($localTestVault)->merge(new PanickingConflictHandler(), $remoteIndex, $localIndex, $lastLocalIndex);
        $indexObject = $mergedIndex->getObjectByPath('file.ext');

        $this->assertInstanceOf(IndexObject::class, $indexObject);
        $this->assertEquals(0777, $indexObject->getPermissions());
        $this->assertEquals(1000, $indexObject->getMtime());
        $this->assertEquals('fcde2b2edba56bf408601fb721fe9b5c338d10ee429ea04fae5511b68fbf8fb9', $indexObject->getHashes()->getHash('sha2-256'));
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
            'sha2-256' => new Sha2_256(),
            'sha2-512' => new Sha2_512(),
        ];
        $fileReader = new FileReader($configuration, $algorithms);
        $hashProvider = new HashProvider($fileReader, $configuration, $algorithms);

        return new StandardIndexMerger($configuration, $hashProvider);
    }
}
