<?php

namespace Archivr\StorageDriver;

use Archivr\Exception\Exception;

class DummyStorageDriver implements StorageDriverInterface
{
    public function read(string $relativePath): string
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }

    public function write(string $relativePath, string $content)
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }

    public function writeStream(string $relativePath, $stream)
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }

    public function exists(string $relativePath): bool
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }

    public function unlink(string $relativePath)
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }

    public function getReadStream(string $relativePath)
    {
        throw new Exception(sprintf('Trying to call %s() on %s.', __FUNCTION__, __CLASS__));
    }
}
