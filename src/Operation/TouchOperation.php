<?php

namespace Archivr\Operation;

class TouchOperation implements OperationInterface
{
    protected $absolutePath;
    protected $mtime;

    public function __construct(string $absolutePath, int $mtime)
    {
        $this->absolutePath = $absolutePath;
        $this->mtime = $mtime;
    }

    public function execute(): bool
    {
        return touch($this->absolutePath, $this->mtime);
    }

    public function __toString(): string
    {
        return sprintf('Touch %s to mtime = %s', $this->absolutePath, date('c', $this->mtime));
    }
}