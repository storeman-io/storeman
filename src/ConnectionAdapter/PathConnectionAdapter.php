<?php

namespace Archivr\ConnectionAdapter;

use Archivr\TildeExpansionTrait;

class PathConnectionAdapter implements ConnectionAdapterInterface
{
    use TildeExpansionTrait;

    /**
     * @var string
     */
    protected $remotePath;

    /**
     * @var resource
     */
    protected $streamContext;


    public function __construct(string $remotePath, $streamContext = null)
    {
        $this->remotePath = rtrim($this->expandTildePath($remotePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->streamContext = $streamContext ?: stream_context_create();
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
