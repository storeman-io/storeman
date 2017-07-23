<?php

namespace Archivr\Operation;

use Archivr\Connection\ConnectionInterface;

class DownloadOperation implements OperationInterface
{
    protected $absolutePath;
    protected $blobId;
    protected $vaultConnection;

    public function __construct(string $absolutePath, string $blobId, ConnectionInterface $vaultConnection)
    {
        $this->absolutePath = $absolutePath;
        $this->blobId = $blobId;
        $this->vaultConnection = $vaultConnection;
    }

    public function execute(): bool
    {
        $localStream = fopen($this->absolutePath, 'w');
        $remoteStream = $this->vaultConnection->getStream($this->blobId, 'r');

        $bytesCopied = stream_copy_to_stream($remoteStream, $localStream);

        fclose($remoteStream);
        fclose($localStream);

        return $bytesCopied !== false;
    }

    public function __toString(): string
    {
        return sprintf('Download %s (blobId %s)', $this->absolutePath, $this->blobId);
    }
}