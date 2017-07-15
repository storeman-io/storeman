<?php

namespace Archivr\Test;

use Symfony\Component\Filesystem\Filesystem;

class TestVault
{
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

        do
        {
            $basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'testVault_' . uniqid() . DIRECTORY_SEPARATOR;
        }
        while($this->filesystem->exists($basePath));

        $this->filesystem->mkdir($basePath);

        $this->basePath = $basePath;
    }

    public function __destruct()
    {
        $this->filesystem->remove($this->basePath);
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

    protected function getAbsolutePath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $relativePath;
    }
}