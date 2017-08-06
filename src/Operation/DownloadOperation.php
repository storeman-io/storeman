<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;

class DownloadOperation implements OperationInterface
{
    protected $absolutePath;
    protected $blobId;
    protected $vaultConnection;
    protected $streamFilterConfigMap;

    public function __construct(string $absolutePath, string $blobId, ConnectionAdapterInterface $vaultConnection, array $streamFilterConfigMap = [])
    {
        $this->absolutePath = $absolutePath;
        $this->blobId = $blobId;
        $this->vaultConnection = $vaultConnection;
        $this->streamFilterConfigMap = $streamFilterConfigMap;
    }

    public function execute(): bool
    {
        $localStream = fopen($this->absolutePath, 'wb');
        $remoteStream = $this->vaultConnection->getReadStream($this->blobId);

        foreach ($this->streamFilterConfigMap as $filterName => $filterParams)
        {
            stream_filter_append($remoteStream, $filterName, STREAM_FILTER_READ, $filterParams);
        }

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
        $filterNames = implode(',', array_keys($this->streamFilterConfigMap)) ?: '-';

        return sprintf('Download %s (blobId %s, filters: %s)', $this->absolutePath, $this->blobId, $filterNames);
    }
}
