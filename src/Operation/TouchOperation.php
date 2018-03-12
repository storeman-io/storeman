<?php

namespace Archivr\Operation;

use Archivr\StorageAdapter\StorageAdapterInterface;

class TouchOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var int
     */
    protected $mtime;

    public function __construct(string $relativePath, int $mtime)
    {
        $this->relativePath = $relativePath;
        $this->mtime = $mtime;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function execute(string $localBasePath, StorageAdapterInterface $storageAdapter): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;

        return touch($absolutePath, $this->mtime);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Touch %s to mtime = %s', $this->relativePath, date('c', $this->mtime));
    }
}
