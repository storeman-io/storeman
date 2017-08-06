<?php

namespace Archivr\Test\ConnectionAdapter;

use Archivr\ConnectionAdapter\DummyConnectionAdapter;
use Archivr\Exception\Exception;
use PHPUnit\Framework\TestCase;

class DummyConnectionTest extends TestCase
{
    public function testRead()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->read('x');
    }

    public function testWrite()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->write('x', 'x');
    }

    public function testWriteStream()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->writeStream('x', fopen('php://memory', 'r+'));
    }

    public function testExists()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->exists('x');
    }

    public function testUnlink()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->unlink('x');
    }

    public function testGetReadStream()
    {
        $this->expectException(Exception::class);

        $connection = new DummyConnectionAdapter();
        $connection->getReadStream('x');
    }
}
