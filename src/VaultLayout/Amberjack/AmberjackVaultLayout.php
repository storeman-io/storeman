<?php

namespace Storeman\VaultLayout\Amberjack;

use Ramsey\Uuid\Uuid;
use Storeman\Exception;
use Storeman\FileReader;
use Storeman\Hash\HashContainer;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Synchronization;
use Storeman\SynchronizationList;
use Storeman\VaultLayout\LazyLoadedIndex;
use Storeman\VaultLayout\VaultLayoutInterface;

class AmberjackVaultLayout implements VaultLayoutInterface
{
    protected const SYNCHRONIZATION_LIST_FILE_NAME = 'sync.log';

    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    public function __construct(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
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

            while (is_array($row = fgetcsv($stream)))
            {
                $synchronization = $this->createSynchronizationFromScalarArray($row);
                $synchronization->setIndex(new LazyLoadedIndex(function() use ($synchronization) {

                    return $this->readIndex($synchronization);
                }));

                $list->addSynchronization($synchronization);
            }

            if (!feof($stream))
            {
                throw new Exception("Corrupt synchronization list detected");
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
        return $this->getSynchronizations()->getSynchronization($revision);
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
    public function writeSynchronization(Synchronization $synchronization, FileReader $fileReader)
    {
        foreach ($synchronization->getIndex() as $indexObject)
        {
            /** @var IndexObject $indexObject */

            if ($indexObject->isFile() && $indexObject->getBlobId() === null)
            {
                $indexObject->setBlobId($this->generateNewBlobId($synchronization->getIndex()));

                $this->storageAdapter->writeStream($indexObject->getBlobId(), $fileReader->getReadStream($indexObject));
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
            $index->addObject($this->createIndexObjectFromScalarArray($row));
        }

        fclose($stream);

        return $index;
    }

    protected function writeIndex(Synchronization $synchronization)
    {
        // write to local temp file
        $stream = tmpfile();
        $filterHandle = stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_WRITE);
        foreach ($synchronization->getIndex() as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $this->indexObjectToScalarArray($object)) === false)
            {
                throw new \RuntimeException();
            }
        }
        stream_filter_remove($filterHandle);
        rewind($stream);

        // upload local file to vault
        $this->storageAdapter->writeStream($this->getIndexFileName($synchronization), $stream);

        fclose($stream);
    }

    protected function writeSynchronizationList(SynchronizationList $synchronizationList)
    {
        // write to local temp file
        $stream = tmpfile();
        $filterHandle = stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_WRITE);
        foreach ($synchronizationList as $synchronization)
        {
            /** @var Synchronization $synchronization */

            if (fputcsv($stream, $this->synchronizationToScalarArray($synchronization)) === false)
            {
                throw new \RuntimeException();
            }
        }
        stream_filter_remove($filterHandle);
        rewind($stream);


        // upload local file to vault
        $this->storageAdapter->writeStream(static::SYNCHRONIZATION_LIST_FILE_NAME, $stream);

        fclose($stream);
    }

    /**
     * Transforms an IndexObject instance into a scalar array suitable for fputcsv().
     *
     * @param IndexObject $indexObject
     * @return array
     */
    protected function indexObjectToScalarArray(IndexObject $indexObject): array
    {
        return [
            $indexObject->getRelativePath(),
            $indexObject->getType(),
            $indexObject->getMtime(),
            $indexObject->getCtime(),
            $indexObject->getPermissions(),
            $indexObject->getSize(),
            $indexObject->getLinkTarget(),
            $indexObject->getBlobId(),
            $indexObject->getHashes() ? $indexObject->getHashes()->serialize() : null,
        ];
    }

    /**
     * Reconstructs an IndexObject instance from a scalar array read by fgetcsv().
     *
     * @param array $array
     * @return IndexObject
     */
    protected function createIndexObjectFromScalarArray(array $array): IndexObject
    {
        return new IndexObject(
            $array[0],
            (int)$array[1],
            (int)$array[2],
            (int)$array[3],
            (int)$array[4],
            ($array[5] !== '') ? (int)$array[5] : null,
            null,
            $array[6] ?: null,
            $array[7] ?: null,
            $array[8] ? (new HashContainer())->unserialize($array[8]) : null
        );
    }

    protected function synchronizationToScalarArray(Synchronization $synchronization): array
    {
        return [
            $synchronization->getRevision(),
            $synchronization->getTime()->getTimestamp(),
            $synchronization->getIdentity(),
        ];
    }

    protected function createSynchronizationFromScalarArray(array $array): Synchronization
    {
        return new Synchronization(
            $array[0],
            \DateTime::createFromFormat('U', $array[1]),
            $array[2]
        );
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
