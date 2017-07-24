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

    public function testCreationDateInjection()
    {
        $index = new Index();

        $this->assertEquals(new \DateTime(), $index->getCreated(), '', 1);

        $injected = new \DateTime();
        $injected->modify('+30 minutes');

        $anotherIndex = new Index($injected);

        $this->assertEquals($injected, $anotherIndex->getCreated());
    }
}