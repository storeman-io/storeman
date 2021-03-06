<?php

namespace Storeman\Test;

use Symfony\Component\Filesystem\Filesystem;

class TemporaryPathGenerator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string[]
     */
    protected $paths = [];

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function __destruct()
    {
        foreach ($this->paths as $path)
        {
            if ($this->filesystem->exists($path))
            {
                $this->filesystem->remove($path);
            }
        }
    }

    public function getTemporaryDirectory(string $prefix = 'tempDir', int $perms = 0777): string
    {
        $path = "{$this->getTemporaryPath($prefix)}/";

        $this->filesystem->mkdir($path, $perms);

        return $path;
    }

    public function getTemporaryFile(string $prefix = 'tempFile', int $perms = 0777): string
    {
        $path = $this->getTemporaryPath($prefix);

        $this->filesystem->touch($path);
        $this->filesystem->chmod($path, $perms);

        return $path;
    }

    public function getNonExistingPath(): string
    {
        do
        {
            $path = sys_get_temp_dir() . uniqid('non-existing');
        }
        while(file_exists($path));

        return $path;
    }

    protected function getTemporaryPath(string $prefix): string
    {
        do
        {
            $path = sys_get_temp_dir() . "/{$prefix}_" . uniqid();
        }
        while($this->filesystem->exists($path));

        $this->paths[] = $path;

        return $path;
    }
}
