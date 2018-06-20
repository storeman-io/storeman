<?php

namespace Storeman\Operation;

use Storeman\FileReader;
use Storeman\VaultLayout\VaultLayoutInterface;

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

    public function __construct(string $relativePath, string $blobId)
    {
        $this->relativePath = $relativePath;
        $this->blobId = $blobId;
    }

    public function execute(string $localBasePath, FileReader $fileReader, VaultLayoutInterface $vaultLayout): bool
    {
        $localStream = fopen($localBasePath . $this->relativePath, 'wb');
        $remoteStream = $vaultLayout->readBlob($this->blobId);

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
        return sprintf('Download %s (blobId %s)', $this->relativePath, $this->blobId);
    }
}
