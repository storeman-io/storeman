<?php

namespace Storeman\Operation;

use Storeman\StorageAdapter\StorageAdapterInterface;

class UnlinkOperation implements OperationInterface
{
    protected $relativePath;

    public function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function execute(string $localBasePath, StorageAdapterInterface $storageAdapter): bool
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
