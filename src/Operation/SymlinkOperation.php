<?php

namespace Archivr\Operation;

class SymlinkOperation implements OperationInterface
{
    protected $absolutePath;
    protected $absoluteLinkTarget;
    protected $mode;

    public function __construct(string $absolutePath, string $absoluteLinkTarget, int $mode)
    {
        $this->absolutePath = $absolutePath;
        $this->absoluteLinkTarget = $absoluteLinkTarget;
        $this->mode = $mode;
    }

    public function execute(): bool
    {
        return symlink($this->absoluteLinkTarget, $this->absolutePath) && chmod($this->absolutePath, $this->mode);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Symlink %s to %s (mode %s)', $this->absolutePath, $this->absoluteLinkTarget, $this->mode);
    }
}