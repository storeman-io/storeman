<?php

namespace Archivr\Operation;

use Archivr\StorageDriver\StorageDriverInterface;

class MkdirOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var int
     */
    protected $mode;

    public function __construct(string $relativePath, int $mode)
    {
        $this->relativePath = $relativePath;
        $this->mode = $mode;
    }

    public function execute(string $localBasePath, StorageDriverInterface $storageDriver): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;

        return mkdir($absolutePath, $this->mode, true);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Mkdir %s (mode: %s)', $this->relativePath, decoct($this->mode));
    }
}
