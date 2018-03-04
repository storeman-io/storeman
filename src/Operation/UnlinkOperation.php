<?php

namespace Archivr\Operation;

use Archivr\StorageDriver\StorageDriverInterface;

class UnlinkOperation implements OperationInterface
{
    protected $relativePath;

    public function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function execute(string $localBasePath, StorageDriverInterface $storageDriver): bool
    {
        return unlink($localBasePath . $this->relativePath);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return "Unlink {$this->relativePath}";
    }
}
