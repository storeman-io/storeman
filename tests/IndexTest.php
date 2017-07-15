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
}