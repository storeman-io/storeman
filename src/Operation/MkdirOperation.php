<?php

namespace Sync\Operation;

class MkdirOperation implements OperationInterface
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
        return mkdir($this->absolutePath, $this->mode, true);
    }

    public function __toString(): string
    {
        return sprintf('Mkdir %s (mode: %s)', $this->absolutePath, decoct($this->mode));
    }
}