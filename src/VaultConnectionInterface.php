<?php

namespace Sync;

interface VaultConnectionInterface
{
    public function acquireLock(bool $wait = true, bool $force = false);
    public function releaseLock();

    public function read(string $relativePath): string;
    public function write(string $relativePath, string $content);

    public function exists(string $relativePath): bool;
    public function unlink(string $relativePath);

    public function getStream(string $relativePath, string $mode);
}