<?php

namespace Archivr\Test;

use Archivr\Vault;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class TestVault implements \IteratorAggregate
{
    use TemporaryPathGeneratorProviderTrait;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->basePath = $this->getTemporaryPathGenerator()->getTemporaryDirectory('testVault');
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getObjectByRelativePath(string $relativePath): SplFileInfo
    {
        $absolutePath = $this->basePath . DIRECTORY_SEPARATOR . $relativePath;

        return new SplFileInfo($absolutePath, dirname($absolutePath), $relativePath);
    }

    public function mkdir(string $relativePath): TestVault
    {
        $this->filesystem->mkdir($this->getAbsolutePath($relativePath));

        return $this;
    }

    public function touch(string $relativePath, int $mtime = null): TestVault
    {
        $this->filesystem->touch($this->getAbsolutePath($relativePath), $mtime);

        return $this;
    }

    public function fwrite(string $relativePath, string $content = ''): TestVault
    {
        $this->filesystem->dumpFile($this->getAbsolutePath($relativePath), $content);

        return $this;
    }

    public function remove(string $relativePath): TestVault
    {
        $this->filesystem->remove($this->getAbsolutePath($relativePath));

        return $this;
    }

    public function getIterator(bool $filterMetaFiles = true): \Iterator
    {
        $finder = new Finder();
        $finder->in($this->basePath);
        $finder->exclude(Vault::METADATA_DIRECTORY_NAME);

        return $finder->getIterator();
    }

    protected function getAbsolutePath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
    }
}
