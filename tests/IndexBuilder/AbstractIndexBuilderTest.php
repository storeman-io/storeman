<?php

namespace Storeman\Test\IndexBuilder;

use PHPUnit\Framework\TestCase;
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
        $indexBuilder->buildIndexFromPath($this->getTemporaryPathGenerator()->getNonExistingPath());
    }

    public function testFromFilePath()
    {
        $this->expectException(\Exception::class);

        $indexBuilder = $this->getIndexBuilder();
        $indexBuilder->buildIndexFromPath($this->getTemporaryPathGenerator()->getTemporaryFile());
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

        $indexBuilder = $this->getIndexBuilder();
        $index = $indexBuilder->buildIndexFromPath($testVault->getBasePath(), ['/.*Ignored$/']);

        $object = $index->getObjectByPath('file.ext');
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertTrue($object->isFile());
        $this->assertEquals(11, $object->getSize());
        $this->assertEquals(4231, $object->getMtime());

        $object = $index->getObjectByPath('my dir');
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertTrue($object->isDirectory());

        $object = $index->getObjectByPath('my dir/another file.bin');
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertTrue($object->isFile());
        $this->assertEquals(7, $object->getSize());

        $object = $index->getObjectByPath('myLink');
        $this->assertInstanceOf(IndexObject::class, $object);
        $this->assertTrue($object->isLink());
        $this->assertEquals('my dir/another file.bin', $object->getLinkTarget());

        $this->assertNull($index->getObjectByPath('toBeIgnored'));
    }

    abstract protected function getIndexBuilder(): IndexBuilderInterface;
}
