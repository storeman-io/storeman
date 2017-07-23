<?php

namespace Archivr\Test;

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

    public function getTemporaryDirectory(string $prefix): string
    {
        $path = $this->getTemporaryPath($prefix);

        $this->filesystem->mkdir($path);

        return $path;
    }

    public function getTemporaryFile(string $prefix): string
    {
        $path = $this->getTemporaryPath($prefix);

        $this->filesystem->touch($path);

        return $path;
    }

    protected function getTemporaryPath(string $prefix): string
    {
        do
        {
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid() . DIRECTORY_SEPARATOR;
        }
        while($this->filesystem->exists($path));

        $this->paths[] = $path;

        return $path;
    }
}