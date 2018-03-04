<?php

namespace Archivr\Test\ConnectionAdapter;

use Archivr\StorageDriver\FlysystemStorageDriver;
use Archivr\Exception\Exception;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use League\Flysystem\Adapter\Local;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FlysystemConnectionAdapterTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function testRead()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $this->getFilesystem()->dumpFile($filePath, $fileContent);

        $this->assertEquals($fileContent, $adapter->read($fileName));

        $this->expectException(Exception::class);

        $adapter->read('non-existent.ext');
    }

    public function testWrite()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $adapter->write($fileName, $fileContent);

        $this->assertEquals($fileContent, file_get_contents($filePath));
    }

    public function testWriteStream()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $fileContent);
        rewind($stream);

        $adapter->writeStream($fileName, $stream);

        $this->assertEquals($fileContent, file_get_contents($filePath));
    }

    public function testExists()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $this->getFilesystem()->dumpFile($filePath, $fileContent);

        $this->assertTrue($adapter->exists($fileName));
        $this->assertFalse($adapter->exists('non-existent.ext'));
    }

    public function testUnlink()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $this->getFilesystem()->dumpFile($filePath, $fileContent);

        $this->assertTrue(is_file($filePath));

        $adapter->unlink($fileName);

        $this->assertFalse(is_file($filePath));

        $this->expectException(Exception::class);

        $adapter->unlink('non-existent.ext');
    }

    public function testGetReadStream()
    {
        /** @var FlysystemStorageDriver $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $this->getFilesystem()->dumpFile($filePath, $fileContent);

        $stream = $adapter->getReadStream($fileName);

        $this->assertEquals($fileContent, stream_get_contents($stream));

        $this->expectException(Exception::class);

        $adapter->getReadStream('non-existent.ext');
    }

    protected function getTestSettings(): array
    {
        $basePath = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $fileName = uniqid() . '.ext';
        $fileContent = md5(mt_rand());

        return [
            $fileName,
            $basePath . $fileName,
            $fileContent,
            new FlysystemStorageDriver(new \League\Flysystem\Filesystem(new Local($basePath)))
        ];
    }

    protected function getFilesystem(): Filesystem
    {
        if ($this->filesystem === null)
        {
            $this->filesystem = new Filesystem();
        }

        return $this->filesystem;
    }
}
