<?php

namespace Storeman\Test\Index;

use Storeman\Hash\HashContainer;
use Storeman\Index\IndexObject;
use PHPUnit\Framework\TestCase;
use Storeman\Test\TestVault;

class IndexObjectTest extends TestCase
{
    public function testFileFromPath()
    {
        $relativePath = 'dir/Some File.ext';

        $testVault = new TestVault();
        $testVault->mkdir('dir');
        $testVault->fwrite($relativePath, random_bytes(random_int(0, 1024)));

        $filePath = $testVault->getBasePath() . $relativePath;
        $indexObject = IndexObject::fromPath($testVault->getBasePath(), $relativePath);

        $this->assertInstanceOf(IndexObject::class, $indexObject);
        $this->assertTrue($indexObject->isFile());
        $this->assertFalse($indexObject->isDirectory());
        $this->assertFalse($indexObject->isLink());
        $this->assertEquals($relativePath, $indexObject->getRelativePath());
        $this->assertEquals(basename($relativePath), $indexObject->getBasename());
        $this->assertEquals(filectime($filePath), $indexObject->getCtime());
        $this->assertEquals(filemtime($filePath), $indexObject->getMtime());
        $this->assertEquals(fileperms($filePath), $indexObject->getMode());
        $this->assertEquals(filesize($filePath), $indexObject->getSize());
        $this->assertInstanceOf(HashContainer::class, $indexObject->getHashes());
    }

    public function testDirectoryFromPath()
    {
        $dirName = 'My Directory';

        $testVault = new TestVault();
        $testVault->mkdir($dirName);

        $dirPath = $testVault->getBasePath() . $dirName;
        $indexObject = IndexObject::fromPath($testVault->getBasePath(), $dirName);

        $this->assertInstanceOf(IndexObject::class, $indexObject);
        $this->assertTrue($indexObject->isDirectory());
        $this->assertEquals($dirName, $indexObject->getRelativePath());
        $this->assertEquals(filectime($dirPath), $indexObject->getCtime());
        $this->assertEquals(filemtime($dirPath), $indexObject->getMtime());
        $this->assertEquals(fileperms($dirPath), $indexObject->getMode());
        $this->assertNull($indexObject->getSize());
        $this->assertNull($indexObject->getHashes());
    }

    public function testLinkFromPath()
    {
        $linkName = 'Some Link';
        $targetName = 'My Target';

        $testVault = new TestVault();
        $testVault->touch($targetName);
        $testVault->link($linkName, $targetName);

        $absolutePath = $testVault->getBasePath() . $linkName;
        $indexObject = IndexObject::fromPath($testVault->getBasePath(), $linkName);

        $this->assertInstanceOf(IndexObject::class, $indexObject);
        $this->assertTrue($indexObject->isLink());
        $this->assertEquals($linkName, $indexObject->getRelativePath());
        $this->assertEquals(filectime($absolutePath), $indexObject->getCtime());
        $this->assertEquals(filemtime($absolutePath), $indexObject->getMtime());
        $this->assertNull($indexObject->getSize());
        $this->assertNull($indexObject->getHashes());
    }

    public function testFromNonExistentPath()
    {
        $this->expectException(\Exception::class);

        IndexObject::fromPath('/tmp', 'non-existent');
    }

    public function testComparison()
    {
        $testVaultA = new TestVault();
        $testVaultA->fwrite('test.ext');
        $testVaultA->fwrite('another.ext');

        $indexObjectA1 = IndexObject::fromPath($testVaultA->getBasePath(), 'test.ext');
        $indexObjectA2 = IndexObject::fromPath($testVaultA->getBasePath(), 'test.ext');
        $indexObjectB = IndexObject::fromPath($testVaultA->getBasePath(), 'another.ext');

        $this->assertTrue($indexObjectA1->equals($indexObjectA2));
        $this->assertFalse($indexObjectA1->equals($indexObjectB));

        $indexObjectA1->setBlobId('xxx');

        $this->assertFalse($indexObjectA1->equals($indexObjectA2));
    }
}
