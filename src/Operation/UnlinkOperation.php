<?php

namespace Archivr\Operation;

class UnlinkOperation implements OperationInterface
{
    protected $absolutePath;

    public function __construct(string $absolutePath)
    {
        $this->absolutePath = $absolutePath;
    }

    public function execute(): bool
    {
        return unlink($this->absolutePath);
    }

    public function __toString(): string
    {
        return "Unlink {$this->absolutePath}";
    }
}