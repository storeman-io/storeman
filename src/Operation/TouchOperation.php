<?php

namespace Archivr\Operation;

use Archivr\StorageDriver\StorageDriverInterface;

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

    public function execute(string $localBasePath, StorageDriverInterface $storageDriver): bool
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
