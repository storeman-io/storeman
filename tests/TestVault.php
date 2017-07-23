<?php

namespace Archivr\Test;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

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

    public function mkdir(string $relativePath): TestVault
    {
        $this->filesystem->mkdir($this->getAbsolutePath($relativePath));

        return $this;
    }

    public function touch(string $relativePath, int $ctime = null): TestVault
    {
        $this->filesystem->touch($this->getAbsolutePath($relativePath), $ctime);

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

    public function getIterator(): \Iterator
    {
        return new \RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->basePath,
            RecursiveDirectoryIterator::CURRENT_AS_FILEINFO |
            RecursiveDirectoryIterator::SKIP_DOTS
        ));
    }

    protected function getAbsolutePath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
    }
}