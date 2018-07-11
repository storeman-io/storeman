<?php

namespace Storeman\Operation;

use Storeman\FileReader;
use Storeman\FilesystemUtility;
use Storeman\VaultLayout\VaultLayoutInterface;

class TouchOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var float
     */
    protected $mtime;

    public function __construct(string $relativePath, float $mtime)
    {
        $this->relativePath = $relativePath;
        $this->mtime = $mtime;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getMtime(): float
    {
        return $this->mtime;
    }

    public function execute(string $localBasePath, FileReader $fileReader, VaultLayoutInterface $vaultLayout): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;
        $time = FilesystemUtility::buildTime($this->mtime);

        exec("touch -m -d '{$time}' {$absolutePath} 2>&1", $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        $timeString = FilesystemUtility::buildTime($this->mtime);

        return "Touch '{$this->relativePath}' to mtime = {$timeString}";
    }
}
