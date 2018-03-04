<?php

namespace Archivr\Test;

use Archivr\Index;
use Archivr\IndexObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testFileObjectAdditionAndRetrieval()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World!');

        $index = new Index();
        $index->addObject(IndexObject::fromPath($testVault->getBasePath(), 'file.ext'));

        $this->assertEquals(1, count($index));

        $indexObject = $index->getObjectByPath('file.ext');

        $this->assertNotNull($indexObject);
        $this->assertTrue($indexObject->isFile());
        $this->assertEquals('file.ext', $indexObject->getRelativePath());
    }

    public function testNullComparison()
    {
        $index = new Index();

        $this->assertFalse($index->equals(null));
    }

    public function testIsSubset()
    {
        $indexA = new Index();
        $indexB = new Index();

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $testVault = new TestVault();
        $testVault->fwrite('first.ext');
        $testVault->fwrite('second.ext');

        $firstObject = IndexObject::fromPath($testVault->getBasePath(), 'first.ext');
        $secondObject = IndexObject::fromPath($testVault->getBasePath(), 'second.ext');

        $this->assertTrue($indexA->isSubsetOf($indexB));

        $indexA->addObject($firstObject);

        $this->assertFalse($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));

        $indexB->addObject($firstObject);

        $this->assertTrue($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));

        $indexA->addObject($secondObject);

        $this->assertFalse($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));
    }

    public function testComparison()
    {
        $indexA = new Index();
        $indexB = new Index();

        $this->assertTrue($this->areIndizesEqual($indexA, $indexA));
        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $testVault = new TestVault();
        $testVault->fwrite('first.ext');
        $testVault->fwrite('second.ext');

        $firstObject = IndexObject::fromPath($testVault->getBasePath(), 'first.ext');
        $secondObject = IndexObject::fromPath($testVault->getBasePath(), 'second.ext');

        $indexA->addObject($firstObject);

        $this->assertFalse($this->areIndizesEqual($indexA, $indexB));

        $indexB->addObject($firstObject);

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $indexB->addObject($secondObject);

        $this->assertFalse($this->areIndizesEqual($indexA, $indexB));

        $indexA->addObject($secondObject);

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));
    }

    protected function areIndizesEqual(Index $indexA, Index $indexB)
    {
        return $indexA->equals($indexB) && $indexB->equals($indexA);
    }
}
