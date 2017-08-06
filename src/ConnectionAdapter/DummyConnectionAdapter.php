<?php

namespace Archivr\ConnectionAdapter;

use Archivr\Exception\Exception;

class DummyConnectionAdapter implements ConnectionAdapterInterface
{
    public function read(string $relativePath): string
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function write(string $relativePath, string $content)
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function writeStream(string $relativePath, $stream)
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function exists(string $relativePath): bool
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function unlink(string $relativePath)
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function getReadStream(string $relativePath)
    {
        throw new Exception('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }
}
