<?php

namespace Storeman\Operation;

use Storeman\FileReader;
use Storeman\VaultLayout\VaultLayoutInterface;

class SymlinkOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var string
     */
    protected $relativeLinkTarget;

    public function __construct(string $relativePath, string $relativeLinkTarget)
    {
        $this->relativePath = $relativePath;
        $this->relativeLinkTarget = $relativeLinkTarget;
    }

    public function execute(string $localBasePath, FileReader $fileReader, VaultLayoutInterface $vaultLayout): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;
        $absoluteLinkTarget = $localBasePath . $this->relativeLinkTarget;

        return symlink($absoluteLinkTarget, $absolutePath);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Symlink %s to %s', $this->relativePath, $this->relativeLinkTarget);
    }
}
