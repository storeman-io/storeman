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

    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function execute(): bool
    {
        return touch($this->absolutePath, $this->mtime);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Touch %s to mtime = %s', $this->absolutePath, date('c', $this->mtime));
    }
}
