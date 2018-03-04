<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class ChmodOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var int
     */
    protected $mode;

    public function __construct(string $relativePath, int $mode)
    {
        $this->relativePath = $relativePath;
        $this->mode = $mode;
    }

    public function execute(string $localBasePath, ConnectionAdapterInterface $connection): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;

        return chmod($absolutePath, $this->mode);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Chmod %s to %s', $this->relativePath, decoct($this->mode));
    }
}
