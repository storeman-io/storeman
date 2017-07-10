<?php

namespace Sync\Operation;

class ChmodOperation implements OperationInterface
{
    protected $absolutePath;
    protected $mode;

    public function __construct(string $absolutePath, int $mode)
    {
        $this->absolutePath = $absolutePath;
        $this->mode = $mode;
    }

    public function execute(): bool
    {
        return chmod($this->absolutePath, $this->mode);
    }

    public function __toString(): string
    {
        return sprintf('Chmod %s to %s', $this->absolutePath, decoct($this->mode));
    }
}