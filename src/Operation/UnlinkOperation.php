<?php

namespace Storeman\Operation;

use Storeman\FileReader;
use Storeman\VaultLayout\VaultLayoutInterface;

class UnlinkOperation implements OperationInterface
{
    protected $relativePath;

    public function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function execute(string $localBasePath, FileReader $fileReader, VaultLayoutInterface $vaultLayout): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;

        if (is_file($absolutePath) || is_link($absolutePath))
        {
            return unlink($localBasePath . $this->relativePath);
        }
        elseif (is_dir($absolutePath))
        {
            return rmdir($absolutePath);
        }

        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return "Unlink {$this->relativePath}";
    }
}
