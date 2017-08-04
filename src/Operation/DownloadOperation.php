<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class DownloadOperation implements OperationInterface
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
        $localStream = fopen($this->absolutePath, 'w');
        $remoteStream = $this->vaultConnection->getReadStream($this->blobId);

        $bytesCopied = stream_copy_to_stream($remoteStream, $localStream);

        fclose($remoteStream);
        fclose($localStream);

        return $bytesCopied !== false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return sprintf('Download %s (blobId %s)', $this->absolutePath, $this->blobId);
    }
}
