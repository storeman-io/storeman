<?php

namespace Archivr\ConnectionAdapter;

class DummyConnectionAdapter implements ConnectionAdapterInterface
{
    public function read(string $relativePath): string
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function write(string $relativePath, string $content)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function writeStream(string $relativePath, $stream)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function exists(string $relativePath): bool
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function unlink(string $relativePath)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }

    public function getReadStream(string $relativePath)
    {
        throw new \RuntimeException('Trying to call ' . __FUNCTION__ . '() on DummyVaultConnection.');
    }
}
