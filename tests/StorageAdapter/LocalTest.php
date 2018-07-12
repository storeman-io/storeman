<?php

namespace Storeman\Test\StorageAdapter;

use Storeman\Config\Configuration;
use Storeman\Exception;
use Storeman\StorageAdapter\Local;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Config\VaultConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LocalTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function testWriteStream()
    {
        /** @var Local $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $fileContent);
        rewind($stream);

        $adapter->writeStream($fileName, $stream);

        $this->assertEquals($fileContent, file_get_contents($filePath));
    }

    public function testExists()
    {
        /** @var Local $adapter */
        list($fileName, $filePath, $fileContent, $adapter) = $this->getTestSettings();

        $this->getFilesystem()->dumpFile($filePath, $fileContent);

        $this->assertTrue($adapter->exists($fileName));
        $this->assertFalse($adapter->exists('non-existent.ext'));
    }

    public function testUnlink()
    {
        /** @var Local $adapter */
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
        /** @var Local $adapter */
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
        $vaultConfiguration = new VaultConfiguration($this->createMock(Configuration::class));
        $vaultConfiguration->setSetting('path', $basePath);

        return [
            $fileName,
            $basePath . $fileName,
            $fileContent,
            new Local($vaultConfiguration),
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
