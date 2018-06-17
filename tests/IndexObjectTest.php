<?php

namespace Storeman\Test;

use Storeman\IndexObject;
use PHPUnit\Framework\TestCase;

class IndexObjectTest extends TestCase
{
    public function testFileFromPath()
    {
        $relativePath = 'dir/Some File.ext';

        $testVault = new TestVault();
        $testVault->mkdir('dir');
        $testVault->fwrite($relativePath, random_bytes(random_int(0, 1024)));

        $filePath = $testVault->getBasePath() . $relativePath;
        $fileIndexObject = IndexObject::fromPath($testVault->getBasePath(), $relativePath);

        $this->assertInstanceOf(IndexObject::class, $fileIndexObject);
        $this->assertTrue($fileIndexObject->isFile());
        $this->assertFalse($fileIndexObject->isDirectory());
        $this->assertFalse($fileIndexObject->isLink());
        $this->assertEquals($relativePath, $fileIndexObject->getRelativePath());
        $this->assertEquals(basename($relativePath), $fileIndexObject->getBasename());
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

        $indexObjectA1 = IndexObject::fromPath($testVaultA->getBasePath(), 'test.ext');
        $indexObjectA2 = IndexObject::fromPath($testVaultA->getBasePath(), 'test.ext');
        $indexObjectB = IndexObject::fromPath($testVaultA->getBasePath(), 'another.ext');

        $this->assertTrue($indexObjectA1->equals($indexObjectA2));
        $this->assertFalse($indexObjectA1->equals($indexObjectB));
    }
}
