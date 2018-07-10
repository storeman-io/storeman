<?php

namespace Storeman\Test\Index;

use PHPUnit\Framework\TestCase;
use Storeman\Index\IndexObject;
use Storeman\Test\TestVault;

class IndexObjectTest extends TestCase
{
    public function testComparison()
    {
        $testVaultA = new TestVault();
        $testVaultA->fwrite('test.ext');
        $testVaultA->fwrite('another.ext');

        $indexObjectA1 = $testVaultA->getIndexObject('test.ext');
        $indexObjectA2 = $testVaultA->getIndexObject('test.ext');
        $indexObjectB = $testVaultA->getIndexObject('another.ext');

        $this->assertTrue($indexObjectA1->equals($indexObjectA2));
        $this->assertFalse($indexObjectA1->equals($indexObjectB));

        $indexObjectA1->setBlobId('xxx');

        $this->assertFalse($indexObjectA1->equals($indexObjectA2));
        $this->assertTrue($indexObjectA1->equals($indexObjectA2, IndexObject::CMP_IGNORE_BLOBID));

        $indexObjectA2->setBlobId('xxx');

        $this->assertTrue($indexObjectA1->equals($indexObjectA2));
        $this->assertTrue($indexObjectA1->equals($indexObjectA2, IndexObject::CMP_IGNORE_BLOBID));

        $indexObjectA1->getHashes()->addHash('func1', 'asd');

        $this->assertTrue($indexObjectA1->equals($indexObjectA2));
        $this->assertTrue($indexObjectA1->equals($indexObjectA2, IndexObject::CMP_IGNORE_BLOBID));

        $indexObjectA2->getHashes()->addHash('func1', 'qwe');

        $this->assertFalse($indexObjectA1->equals($indexObjectA2));
        $this->assertFalse($indexObjectA1->equals($indexObjectA2, IndexObject::CMP_IGNORE_BLOBID));
    }

    public function testHashCloning()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'foobar');

        $object = $testVault->getIndex()->getObjectByPath('file.ext');
        $object->getHashes()->addHash('x', 'x');

        $clone = clone $object;
        $clone->getHashes()->addHash('y', 'y');

        $this->assertEquals('x', $object->getHashes()->getHash('x'));
        $this->assertNull($object->getHashes()->getHash('y'));
    }
}
