<?php

namespace Storeman\Operation;

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

    /**
     * @var int
     */
    protected $mode;

    public function __construct(string $relativePath, string $relativeLinkTarget, int $mode)
    {
        $this->relativePath = $relativePath;
        $this->relativeLinkTarget = $relativeLinkTarget;
        $this->mode = $mode;
    }

    public function execute(string $localBasePath, VaultLayoutInterface $vaultLayout): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;
        $absoluteLinkTarget = $localBasePath . $this->relativeLinkTarget;

        return symlink($absoluteLinkTarget, $absolutePath) && chmod($absolutePath, $this->mode);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Symlink %s to %s (mode %s)', $this->relativePath, $this->relativeLinkTarget, $this->mode);
    }
}
