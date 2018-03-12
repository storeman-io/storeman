<?php

namespace Archivr\Operation;

use Archivr\StorageAdapter\StorageAdapterInterface;

class DownloadOperation implements OperationInterface
{
    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var string
     */
    protected $blobId;

    /**
     * @var array
     */
    protected $streamFilterConfigMap;

    public function __construct(string $relativePath, string $blobId, array $streamFilterConfigMap = [])
    {
        $this->relativePath = $relativePath;
        $this->blobId = $blobId;
        $this->streamFilterConfigMap = $streamFilterConfigMap;
    }

    public function execute(string $localBasePath, StorageAdapterInterface $storageAdapter): bool
    {
        $localStream = fopen($localBasePath . $this->relativePath, 'wb');
        $remoteStream = $storageAdapter->getReadStream($this->blobId);

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

        return sprintf('Download %s (blobId %s, filters: %s)', $this->relativePath, $this->blobId, $filterNames);
    }
}
