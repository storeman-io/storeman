<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\Exception\Exception;

class UploadOperation implements OperationInterface
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
        $localStream = fopen($this->absolutePath, 'rb');

        foreach ($this->streamFilterConfigMap as $filterName => $filterParams)
        {
            stream_filter_append($localStream, $filterName, STREAM_FILTER_READ, $filterParams);
        }

        try
        {
            $this->vaultConnection->writeStream($this->blobId, $localStream);

            fclose($localStream);

            return true;
        }
        catch (Exception $exception)
        {
            return false;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        $filterNames = implode(',', array_keys($this->streamFilterConfigMap)) ?: '-';

        return sprintf('Upload %s (blobId %s, filters: %s)', $this->absolutePath, $this->blobId, $filterNames);
    }
}
