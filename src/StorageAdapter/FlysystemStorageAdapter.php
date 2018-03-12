<?php

namespace Archivr\StorageAdapter;

use Archivr\Exception\Exception;
use League\Flysystem\Exception as FlysystemException;
use League\Flysystem\Filesystem;

class FlysystemStorageAdapter implements StorageAdapterInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function read(string $relativePath): string
    {
        try
        {
            $content = $this->filesystem->read($relativePath);

            if (!is_string($content))
            {
                throw new Exception(sprintf('read() failed for %s.', $relativePath));
            }
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }

        return $content;
    }

    public function write(string $relativePath, string $content)
    {
        try
        {
            $success = $this->filesystem->put($relativePath, $content);

            if (!$success)
            {
                throw new Exception(sprintf('write() failed for %s.', $relativePath));
            }
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function writeStream(string $relativePath, $stream)
    {
        try
        {
            $success = $this->filesystem->putStream($relativePath, $stream);

            if (!$success)
            {
                throw new Exception(sprintf('writeStream() failed for %s.', $relativePath));
            }
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function exists(string $relativePath): bool
    {
        try
        {
            return $this->filesystem->has($relativePath);
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function unlink(string $relativePath)
    {
        try
        {
            $success = $this->filesystem->delete($relativePath);

            if (!$success)
            {
                throw new Exception(sprintf('unlink() failed for %s', $relativePath));
            }
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function getReadStream(string $relativePath)
    {
        try
        {
            $stream = $this->filesystem->readStream($relativePath);

            if (!is_resource($stream))
            {
                throw new Exception(sprintf('getReadStream() failed for %s.', $relativePath));
            }

            return $stream;
        }
        catch (FlysystemException $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }
}
