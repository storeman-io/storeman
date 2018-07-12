<?php

namespace Storeman\Test\StorageAdapter;

use PHPUnit\Framework\TestCase;
use Storeman\StorageAdapter\StorageAdapterInterface;

abstract class AbstractStorageAdapterTest extends TestCase
{
    public function testRoundtrip()
    {
        $adapter = $this->getStorageAdapter();

        $objectName = uniqid('test_file_');
        $objectData = random_bytes(rand(10, 100));

        $this->assertFalse($adapter->exists($objectName));

        $writeStream = fopen('php://temp', 'w+b');

        $this->assertNotFalse(fwrite($writeStream, $objectData));
        $this->assertTrue(rewind($writeStream));

        $adapter->writeStream($objectName, $writeStream);

        $this->assertTrue($adapter->exists($objectName));

        $readStream = $adapter->getReadStream($objectName);

        $this->assertTrue(is_resource($readStream));
        $this->assertEquals($objectData, stream_get_contents($readStream));

        $adapter->unlink($objectName);

        $this->assertFalse($adapter->exists($objectName));
    }

    abstract protected function getStorageAdapter(): StorageAdapterInterface;
}
