<?php

namespace Storeman\StorageAdapter;

use Storeman\Exception;
use League\Flysystem\Filesystem;

abstract class FlysystemStorageAdapter extends AbstractStorageAdapter
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    public function writeStream(string $relativePath, $stream)
    {
        $this->logger->debug("Writing stream to {$relativePath}...");

        try
        {
            $success = $this->filesystem->putStream($relativePath, $stream);

            if (!$success)
            {
                throw new Exception(sprintf('writeStream() failed for %s.', $relativePath));
            }
        }
        catch (\Exception $exception)
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
        catch (\Exception $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function unlink(string $relativePath)
    {
        $this->logger->debug("Deleting {$relativePath}...");

        try
        {
            $success = $this->filesystem->delete($relativePath);

            if (!$success)
            {
                throw new Exception(sprintf('unlink() failed for %s', $relativePath));
            }
        }
        catch (\Exception $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }

    public function getReadStream(string $relativePath)
    {
        $this->logger->debug("Getting read stream for {$relativePath}...");

        try
        {
            $stream = $this->filesystem->readStream($relativePath);

            if (!is_resource($stream))
            {
                throw new Exception(sprintf('getReadStream() failed for %s.', $relativePath));
            }

            return $stream;
        }
        catch (\Exception $exception)
        {
            throw new Exception($exception->getMessage(), 0, $exception);
        }
    }
}
