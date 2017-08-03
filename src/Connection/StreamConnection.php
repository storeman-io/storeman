<?php

namespace Archivr\Connection;

use Archivr\TildeExpansionTrait;

class StreamConnection implements ConnectionInterface
{
    use TildeExpansionTrait;


    const LOCK_FILE_NAME = 'lockfile';


    /**
     * @var string
     */
    protected $remotePath;

    /**
     * @var resource
     */
    protected $streamContext;

    /**
     * @var bool
     */
    protected $lockAcquired = false;


    public function __construct(string $remotePath, $streamContext = null)
    {
        $this->remotePath = rtrim($this->expandTildePath($remotePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->streamContext = $streamContext ?: stream_context_create();
    }

    public function __destruct()
    {
        $this->releaseLock();
    }

    public function hasLock(): bool
    {
        return $this->lockAcquired;
    }

    public function acquireLock(bool $wait = true, bool $force = false)
    {
        if (!$this->lockAcquired)
        {
            $lockExists = false;

            // check for existing lock
            if (!$force)
            {
                do
                {
                    $lockExists = $this->exists(static::LOCK_FILE_NAME);

                    if (!$lockExists)
                    {
                        // no other lock present
                        break;
                    }

                    // sleep and try again
                    if ($wait)
                    {
                        sleep(5);
                    }
                }
                while ($wait);
            }

            // only write lock if no other exists (or $force is true)
            if (!$lockExists)
            {
                $this->write(static::LOCK_FILE_NAME, getmypid());

                $this->lockAcquired = true;
            }
        }

        return $this->lockAcquired;
    }

    public function releaseLock()
    {
        if ($this->lockAcquired)
        {
            $this->unlink(static::LOCK_FILE_NAME);

            $this->lockAcquired = false;
        }

        return !$this->lockAcquired;
    }

    public function getStream(string $relativePath, string $mode)
    {
        $absolutePath = $this->getAbsolutePath($relativePath);
        $remoteHandle = fopen($absolutePath, $mode, null, $this->streamContext);

        if ($remoteHandle === false)
        {
            throw new \RuntimeException('fopen() failed for ' . $absolutePath);
        }

        return $remoteHandle;
    }

    public function exists(string $relativePath): bool
    {
        try
        {
            @fclose($this->getStream($relativePath, 'r'));
        }
        catch (\RuntimeException $exception)
        {
            return false;
        }

        return true;
    }

    public function read(string $relativePath): string
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        $content = file_get_contents($absolutePath, false, $this->streamContext);

        if ($content === false)
        {
            throw new \RuntimeException('file_get_contents() failed for ' . $absolutePath);
        }

        return $content;
    }

    public function write(string $relativePath, string $content)
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (file_put_contents($absolutePath, $content, 0, $this->streamContext) === false)
        {
            throw new \RuntimeException(sprintf('file_put_contents() failed for %s', $absolutePath));
        }
    }

    public function unlink(string $relativePath)
    {
        $absolutePath = $this->getAbsolutePath($relativePath);

        if (!unlink($absolutePath, $this->streamContext))
        {
            throw new \RuntimeException(sprintf('unlink() failed for %s', $absolutePath));
        }
    }

    protected function getAbsolutePath($relativePath): string
    {
        return $this->remotePath . ltrim($relativePath, DIRECTORY_SEPARATOR);
    }
}
