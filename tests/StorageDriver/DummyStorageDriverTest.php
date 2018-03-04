<?php

namespace Archivr\Test\StorageDriver;

use Archivr\StorageDriver\DummyStorageDriver;
use Archivr\Exception\Exception;
use PHPUnit\Framework\TestCase;

class DummyStorageDriverTest extends TestCase
{
    public function testRead()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->read('x');
    }

    public function testWrite()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->write('x', 'x');
    }

    public function testWriteStream()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->writeStream('x', fopen('php://memory', 'r+'));
    }

    public function testExists()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->exists('x');
    }

    public function testUnlink()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->unlink('x');
    }

    public function testGetReadStream()
    {
        $this->expectException(Exception::class);

        $connection = new DummyStorageDriver();
        $connection->getReadStream('x');
    }
}
