<?php

namespace Archivr\Operation;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\Exception\Exception;

class UploadOperation implements OperationInterface
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

    public function execute(string $localBasePath, ConnectionAdapterInterface $connection): bool
    {
        $absolutePath = $localBasePath . $this->relativePath;

        $localStream = fopen($absolutePath, 'rb');

        foreach ($this->streamFilterConfigMap as $filterName => $filterParams)
        {
            stream_filter_append($localStream, $filterName, STREAM_FILTER_READ, $filterParams);
        }

        try
        {
            $connection->writeStream($this->blobId, $localStream);

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

        return sprintf('Upload %s (blobId %s, filters: %s)', $this->relativePath, $this->blobId, $filterNames);
    }
}
