<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class UploadOperation implements OperationInterface
{
    protected $absolutePath;
    protected $blobId;
    protected $vaultConnection;

    public function __construct(string $absolutePath, string $blobId, ConnectionAdapterInterface $vaultConnection)
    {
        $this->absolutePath = $absolutePath;
        $this->blobId = $blobId;
        $this->vaultConnection = $vaultConnection;
    }

    public function execute(): bool
    {
        $localStream = fopen($this->absolutePath, 'rb');

        try
        {
            $this->vaultConnection->writeStream($this->blobId, $localStream);
        }
        catch (\RuntimeException $exception)
        {
            return false;
        }

        fclose($localStream);

        return true;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Upload %s (blobId %s)', $this->absolutePath, $this->blobId);
    }
}
