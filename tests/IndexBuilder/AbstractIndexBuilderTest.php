<?php

namespace Storeman\Test\IndexBuilder;

use PHPUnit\Framework\TestCase;
use Storeman\FilesystemUtility;
use Storeman\Hash\HashContainer;
use Storeman\Index\IndexObject;
use Storeman\IndexBuilder\IndexBuilderInterface;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;

abstract class AbstractIndexBuilderTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testFromNonExistingPath()
    {
        $this->expectException(\Exception::class);

        $indexBuilder = $this->getIndexBuilder();
        $indexBuilder->buildIndex($this->getTemporaryPathGenerator()->getNonExistingPath());
    }

    public function testFromFilePath()
    {
        $this->expectException(\Exception::class);

        $indexBuilder = $this->getIndexBuilder();
        $indexBuilder->buildIndex($this->getTemporaryPathGenerator()->getTemporaryFile());
    }

    public function test()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World');
        $testVault->touch('file.ext', 4231);
        $testVault->mkdir('my dir');
        $testVault->fwrite('my dir/another file.bin', 'Foo Bar');
        $testVault->link('myLink', 'my dir/another file.bin');
        $testVault->fwrite('toBeIgnored', 'abc');
        $testVault->touch('my dir', 1324);

        $indexBuilder = $this->getIndexBuilder();
        $index = $indexBuilder->buildIndex($testVault->getBasePath(), ['/.*Ignored$/']);

        $relativePath = 'file.ext';
        $object = $index->getObjectByPath($relativePath);
        $absoluteFilePath = "{$testVault->getBasePath()}/{$relativePath}";
        $stat = FilesystemUtility::lstat($absoluteFilePath);
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertEquals($relativePath, $object->getRelativePath());
        $this->assertTrue($object->isFile());
        $this->assertFalse($object->isDirectory());
        $this->assertFalse($object->isLink());
        $this->assertEquals($stat['size'], $object->getSize());
        $this->assertEquals($stat['mtime'], $object->getMtime());
        $this->assertEquals($stat['ctime'], $object->getCtime());
        $this->assertEquals($stat['ino'], $object->getInode());
        $this->assertEquals($stat['mode'] & 0777, $object->getPermissions());
        $this->assertNull($object->getLinkTarget());
        $this->assertInstanceOf(HashContainer::class, $object->getHashes());

        $relativePath = 'my dir';
        $object = $index->getObjectByPath($relativePath);
        $absoluteFilePath = "{$testVault->getBasePath()}/{$relativePath}";
        $stat = FilesystemUtility::lstat($absoluteFilePath);
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertEquals($relativePath, $object->getRelativePath());
        $this->assertFalse($object->isFile());
        $this->assertTrue($object->isDirectory());
        $this->assertFalse($object->isLink());
        $this->assertNull($object->getSize());
        $this->assertEquals($stat['mtime'], $object->getMtime());
        $this->assertEquals($stat['ctime'], $object->getCtime());
        $this->assertEquals($stat['ino'], $object->getInode());
        $this->assertEquals($stat['mode'] & 0777, $object->getPermissions());
        $this->assertNull($object->getLinkTarget());
        $this->assertNull($object->getHashes());

        $relativePath = 'my dir/another file.bin';
        $object = $index->getObjectByPath($relativePath);
        $absoluteFilePath = "{$testVault->getBasePath()}/{$relativePath}";
        $stat = FilesystemUtility::lstat($absoluteFilePath);
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertEquals($relativePath, $object->getRelativePath());
        $this->assertTrue($object->isFile());
        $this->assertFalse($object->isDirectory());
        $this->assertFalse($object->isLink());
        $this->assertEquals($stat['size'], $object->getSize());
        $this->assertEquals($stat['mtime'], $object->getMtime());
        $this->assertEquals($stat['ctime'], $object->getCtime());
        $this->assertEquals($stat['ino'], $object->getInode());
        $this->assertEquals($stat['mode'] & 0777, $object->getPermissions());
        $this->assertNull($object->getLinkTarget());
        $this->assertInstanceOf(HashContainer::class, $object->getHashes());

        $relativePath = 'myLink';
        $object = $index->getObjectByPath($relativePath);
        $absoluteFilePath = "{$testVault->getBasePath()}/{$relativePath}";
        $stat = FilesystemUtility::lstat($absoluteFilePath);
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertEquals($relativePath, $object->getRelativePath());
        $this->assertFalse($object->isFile());
        $this->assertFalse($object->isDirectory());
        $this->assertTrue($object->isLink());
        $this->assertNull($object->getSize());
        $this->assertEquals($stat['mtime'], $object->getMtime());
        $this->assertEquals($stat['ctime'], $object->getCtime());
        $this->assertEquals($stat['ino'], $object->getInode());
        $this->assertEquals($stat['mode'] & 0777, $object->getPermissions());
        $this->assertEquals(readlink($absoluteFilePath), $object->getLinkTarget());
        $this->assertNull($object->getHashes());

        $this->assertNull($index->getObjectByPath('toBeIgnored'));
    }

    abstract protected function getIndexBuilder(): IndexBuilderInterface;
}
