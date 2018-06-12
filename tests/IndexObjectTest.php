<?php

namespace Storeman\Test;

use Storeman\IndexObject;
use PHPUnit\Framework\TestCase;

class IndexObjectTest extends TestCase
{
    public function testFileFromPath()
    {
        $fileName = 'Some File.ext';

        $testVault = new TestVault();
        $testVault->fwrite($fileName, random_bytes(random_int(0, 1024)));

        $filePath = $testVault->getBasePath() . $fileName;
        $fileIndexObject = IndexObject::fromPath($testVault->getBasePath(), $fileName);

        $this->assertInstanceOf(IndexObject::class, $fileIndexObject);
        $this->assertTrue($fileIndexObject->isFile());
        $this->assertEquals($fileName, $fileIndexObject->getRelativePath());
        $this->assertEquals(filectime($filePath), $fileIndexObject->getCtime());
        $this->assertEquals(filemtime($filePath), $fileIndexObject->getMtime());
        $this->assertEquals(fileperms($filePath), $fileIndexObject->getMode());
        $this->assertEquals(filesize($filePath), $fileIndexObject->getSize());
    }

    public function testDirectoryFromPath()
    {
        $dirName = 'My Directory';

        $testVault = new TestVault();
        $testVault->mkdir($dirName);

        $dirPath = $testVault->getBasePath() . $dirName;
        $fileIndexObject = IndexObject::fromPath($testVault->getBasePath(), $dirName);

        $this->assertInstanceOf(IndexObject::class, $fileIndexObject);
        $this->assertTrue($fileIndexObject->isDirectory());
        $this->assertEquals($dirName, $fileIndexObject->getRelativePath());
        $this->assertEquals(filectime($dirPath), $fileIndexObject->getCtime());
        $this->assertEquals(filemtime($dirPath), $fileIndexObject->getMtime());
        $this->assertEquals(fileperms($dirPath), $fileIndexObject->getMode());
        $this->assertNull($fileIndexObject->getSize());
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

        $indexObjectA = IndexObject::fromPath($testVaultA->getBasePath(), 'test.ext');
        $indexObjectB = IndexObject::fromPath($testVaultA->getBasePath(), 'another.ext');

        $this->assertTrue($indexObjectA == $indexObjectA);
        $this->assertFalse($indexObjectA == $indexObjectB);
    }
}
