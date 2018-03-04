<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class UnlinkOperation implements OperationInterface
{
    protected $relativePath;

    public function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function execute(string $localBasePath, ConnectionAdapterInterface $connection): bool
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
