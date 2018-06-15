<?php

namespace Storeman\VaultLayout\Amberjack;

use Ramsey\Uuid\Uuid;
use Storeman\Index;
use Storeman\IndexObject;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Synchronization;
use Storeman\SynchronizationList;
use Storeman\VaultConfiguration;
use Storeman\VaultLayout\LazyLoadedIndex;
use Storeman\VaultLayout\VaultLayoutInterface;

class AmberjackVaultLayout implements VaultLayoutInterface
{
    protected const SYNCHRONIZATION_LIST_FILE_NAME = 'sync.log';

    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    /**
     * @var VaultConfiguration
     */
    protected $vaultConfiguration;

    public function __construct(StorageAdapterInterface $storageAdapter, VaultConfiguration $vaultConfiguration)
    {
        $this->storageAdapter = $storageAdapter;
        $this->vaultConfiguration = $vaultConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getSynchronizations(): SynchronizationList
    {
        if ($this->storageAdapter->exists(static::SYNCHRONIZATION_LIST_FILE_NAME))
        {
            $stream = $this->storageAdapter->getReadStream(static::SYNCHRONIZATION_LIST_FILE_NAME);

            stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ);

            $list = new SynchronizationList();

            while (($row = fgetcsv($stream)) !== false)
            {
                $synchronization = Synchronization::fromScalarArray($row);
                $synchronization->setIndex(new LazyLoadedIndex(function() use ($synchronization) {

                    return $this->readIndex($synchronization);
                }));

                $list->addSynchronization($synchronization);
            }

            fclose($stream);

            return $list;
        }

        return new SynchronizationList();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastSynchronization(): ?Synchronization
    {
        return $this->getSynchronizations()->getLastSynchronization();
    }

    /**
     * {@inheritdoc}
     */
    public function getSynchronization(int $revision): Synchronization
    {
        return $this->getSynchronizations()->getSynchronizationByRevision($revision);
    }

    /**
     * {@inheritdoc}
     */
    public function readBlob(string $blobId)
    {
        $stream = $this->storageAdapter->getReadStream($blobId);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function writeSynchronization(Synchronization $synchronization)
    {
        foreach ($synchronization->getIndex() as $indexObject)
        {
            /** @var IndexObject $indexObject */

            if ($indexObject->isFile() && $indexObject->getBlobId() === null)
            {
                $indexObject->setBlobId($this->generateNewBlobId($synchronization->getIndex()));

                $this->writeFileIndexObject($indexObject);
            }
        }

        $this->writeIndex($synchronization);

        $synchronizationList = $this->getSynchronizations();
        $synchronizationList->addSynchronization($synchronization);

        $this->writeSynchronizationList($synchronizationList);
    }

    protected function readIndex(Synchronization $synchronization): Index
    {
        $stream = $this->storageAdapter->getReadStream($this->getIndexFileName($synchronization));

        stream_filter_append($stream, 'zlib.inflate');

        $index = new Index();
        while (($row = fgetcsv($stream)) !== false)
        {
            $index->addObject(IndexObject::fromScalarArray($row));
        }

        fclose($stream);

        return $index;
    }

    protected function writeIndex(Synchronization $synchronization)
    {
        // write to local temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'index');
        $stream = fopen($tempPath, 'w+b');
        foreach ($synchronization->getIndex() as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $object->toScalarArray()) === false)
            {
                throw new \RuntimeException();
            }
        }
        rewind($stream);

        // upload local file to vault
        stream_filter_append($stream, 'zlib.deflate');
        $this->storageAdapter->writeStream($this->getIndexFileName($synchronization), $stream);

        fclose($stream);
    }

    protected function writeFileIndexObject(IndexObject $indexObject)
    {
        assert($indexObject->isFile());
        assert($indexObject->getBlobId() !== null);

        $localPath = $this->vaultConfiguration->getConfiguration()->getPath() . $indexObject->getRelativePath();

        $stream = fopen($localPath, 'rb');

        $this->storageAdapter->writeStream($indexObject->getBlobId(), $stream);
    }

    protected function writeSynchronizationList(SynchronizationList $synchronizationList)
    {
        // write to local temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'synchronizationList');
        $stream = fopen($tempPath, 'w+b');
        foreach ($synchronizationList as $synchronization)
        {
            /** @var Synchronization $synchronization */

            if (fputcsv($stream, $synchronization->toScalarArray()) === false)
            {
                throw new \RuntimeException();
            }
        }
        rewind($stream);

        // upload local file to vault
        stream_filter_append($stream, 'zlib.deflate');
        $this->storageAdapter->writeStream(static::SYNCHRONIZATION_LIST_FILE_NAME, $stream);

        fclose($stream);
    }

    protected function generateNewBlobId(Index $index = null): string
    {
        do
        {
            $blobId = Uuid::uuid4()->toString();
        }
        while (($index && $index->getObjectByBlobId($blobId)) || $this->storageAdapter->exists($blobId));

        return $blobId;
    }

    protected function getIndexFileName(Synchronization $synchronization): string
    {
        return "index_{$synchronization->getRevision()}";
    }
}
