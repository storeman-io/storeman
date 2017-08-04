<?php

namespace Archivr\ConnectionAdapter;

use League\Flysystem\Filesystem;

class FlysystemConnectionAdapter implements ConnectionAdapterInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->filesystem->getConfig()->set('disable_asserts', true);
    }

    public function read(string $relativePath): string
    {
        $content = $this->filesystem->read($relativePath);

        if (!is_string($content))
        {
            throw new \RuntimeException(sprintf('read() failed for %s.', $relativePath));
        }

        return $content;
    }

    public function write(string $relativePath, string $content)
    {
        $success = $this->filesystem->write($relativePath, $content);

        if (!$success)
        {
            throw new \RuntimeException(sprintf('write() failed for %s.', $relativePath));
        }
    }

    public function writeStream(string $relativePath, $stream)
    {
        $success = $this->filesystem->writeStream($relativePath, $stream);

        if (!$success)
        {
            throw new \RuntimeException(sprintf('writeStream() failed for %s.', $relativePath));
        }
    }

    public function exists(string $relativePath): bool
    {
        return $this->filesystem->has($relativePath);
    }

    public function unlink(string $relativePath)
    {
        $success = $this->filesystem->delete($relativePath);

        if (!$success)
        {
            throw new \RuntimeException(sprintf('unlink() failed for %s', $relativePath));
        }
    }

    public function getReadStream(string $relativePath)
    {
        $stream = $this->filesystem->readStream($relativePath);

        if (!is_resource($stream))
        {
            throw new \RuntimeException(sprintf('getReadStream() failed for %s.', $relativePath));
        }

        return $stream;
    }
}
