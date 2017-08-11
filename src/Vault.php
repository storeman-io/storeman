<?php

namespace Archivr;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\Exception\Exception;
use Archivr\IndexMerger\IndexMergerInterface;
use Archivr\IndexMerger\StandardIndexMerger;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Archivr\Operation\ChmodOperation;
use Archivr\Operation\DownloadOperation;
use Archivr\Operation\MkdirOperation;
use Archivr\Operation\OperationInterface;
use Archivr\Operation\SymlinkOperation;
use Archivr\Operation\TouchOperation;
use Archivr\Operation\UnlinkOperation;
use Archivr\Operation\UploadOperation;

class Vault
{
    use TildeExpansionTrait;

    const METADATA_DIRECTORY_NAME = '.archivr';
    const SYNCHRONIZATION_LIST_FILE_NAME = 'index';
    const LOCK_SYNC = 'sync';

    /**
     * @var string
     */
    protected $title;

    /**
     * @var ConnectionAdapterInterface
     */
    protected $vaultConnection;

    /**
     * @var string
     */
    protected $localPath;

    /**
     * @var LockAdapterInterface
     */
    protected $lockAdapter;

    /**
     * @var IndexMergerInterface
     */
    protected $indexMerger;

    /**
     * @var string[]
     */
    protected $exclusions = [];


    public function __construct(string $title, string $localPath, ConnectionAdapterInterface $vaultConnection)
    {
        $this->title = $title;
        $this->vaultConnection = $vaultConnection;
        $this->localPath = rtrim($this->expandTildePath($localPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLocalPath(): string
    {
        return $this->localPath;
    }

    public function setIndexMerger(IndexMergerInterface $indexMerger = null)
    {
        $this->indexMerger = $indexMerger;

        return $this;
    }

    public function getIndexMerger(): IndexMergerInterface
    {
        if ($this->indexMerger === null)
        {
            $this->indexMerger = new StandardIndexMerger();
        }

        return $this->indexMerger;
    }

    public function setLockAdapter(LockAdapterInterface $lockAdapter = null): Vault
    {
        $this->lockAdapter = $lockAdapter;

        return $this;
    }

    public function getLockAdapter(): LockAdapterInterface
    {
        if ($this->lockAdapter === null)
        {
            $this->lockAdapter = new ConnectionBasedLockAdapter($this->vaultConnection);
        }

        return $this->lockAdapter;
    }

    public function getVaultConnection(): ConnectionAdapterInterface
    {
        return $this->vaultConnection;
    }

    public function getExclusions(): array
    {
        return $this->exclusions;
    }

    public function addExclusion(string $path): Vault
    {
        $this->exclusions[] = $path;

        return $this;
    }

    public function setExclusions(array $paths): Vault
    {
        $this->exclusions = array_values($paths);

        return $this;
    }

    /**
     * Builds and returns an index representing the current local state.
     *
     * @return Index
     */
    public function buildLocalIndex(): Index
    {
        $finder = new Finder();
        $finder->in($this->localPath);
        $finder->ignoreDotFiles(false);
        $finder->ignoreVCS(true);
        $finder->exclude(static::METADATA_DIRECTORY_NAME);
        $finder->notPath('archivr.json');

        foreach ($this->exclusions as $path)
        {
            $finder->notPath($path);
        }

        $index = new Index();

        foreach ($finder->directories() as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $index->addObject(IndexObject::fromPath($this->localPath, $fileInfo->getRelativePathname()));
        }

        foreach ($finder->files() as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $index->addObject(IndexObject::fromPath($this->localPath, $fileInfo->getRelativePathname()));
        }

        return $index;
    }

    /**
     * Reads and returns the index representing the local state on the last synchronization.
     *
     * @return Index
     * @throws Exception
     */
    public function loadLastLocalIndex()
    {
        $index = null;
        $path = $this->getLastLocalIndexFilePath();

        if (is_file($path))
        {
            $stream = fopen($path, 'rb');
            $indexModificationDate = \DateTime::createFromFormat('U', filemtime($path));

            if (!($indexModificationDate instanceof \DateTime))
            {
                throw new Exception();
            }

            $index = $this->readIndexFromStream($stream, $indexModificationDate);

            fclose($stream);
        }

        return $index;
    }

    /**
     * Reads and returns the current remote index.
     *
     * @param int $revision Revision to load. Defaults to the last revision.
     *
     * @return Index
     */
    public function loadRemoteIndex(int $revision = null)
    {
        $list = null;

        if ($revision === null)
        {
            $list = $this->loadSynchronizationList();

            if (!$list || !$list->getLastSynchronization())
            {
                return null;
            }

            $revision = $list->getLastSynchronization()->getRevision();
        }

        return $this->doLoadRemoteIndex($revision, $list);
    }

    /**
     * Computes and returns the index representing the vault state after the local index has been merged with the remote index.
     *
     * @return Index
     */
    public function buildMergedIndex(): Index
    {
        return $this->doBuildMergedIndex();
    }

    /**
     * Returns ordered collection of operations required to synchronize the vault with the local path.
     * In addition to the object specific operations contained in the returned OperationCollection additional operations
     * might be necessary like index updates that do not belong to specific index objects.
     *
     * @return OperationCollection
     */
    public function getOperationCollection(): OperationCollection
    {
        return $this->doGetOperationCollection();
    }

    /**
     * Synchronizes the local with the remote state by executing all operations returned by getOperationCollection() (broadly speaking).
     *
     * @param int $newRevision
     * @param string $identity
     * @param SynchronizationProgressListenerInterface $progressionListener
     *
     * @return OperationResultCollection
     * @throws Exception
     */
    public function synchronize(int $newRevision = null, string $identity = null, SynchronizationProgressListenerInterface $progressionListener = null): OperationResultCollection
    {
        if ($progressionListener === null)
        {
            $progressionListener = new DummySynchronizationProgressListener();
        }

        $localIndex = $this->buildLocalIndex();
        $lastLocalIndex = $this->loadLastLocalIndex();


        if (!$this->getLockAdapter()->acquireLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to acquire lock.');
        }


        $synchronizationList = $this->loadSynchronizationList() ?: new SynchronizationList();
        $lastSynchronization = $synchronizationList->getLastSynchronization();

        if ($lastSynchronization)
        {
            $newRevision = $newRevision ?: ($lastSynchronization->getRevision() + 1);
            $remoteIndex = $this->doLoadRemoteIndex($lastSynchronization->getRevision(), $synchronizationList);
        }
        else
        {
            $newRevision = $newRevision ?: 1;
            $remoteIndex = null;
        }

        $synchronization = new Synchronization($newRevision, $this->generateNewBlobId(), new \DateTime(), $identity);
        $synchronizationList->addSynchronization($synchronization);


        $mergedIndex = $this->doBuildMergedIndex($localIndex, $lastLocalIndex, $remoteIndex);
        $operationCollection = $this->doGetOperationCollection($localIndex, $remoteIndex, $mergedIndex);

        $operationResultCollection = new OperationResultCollection();

        // operation count +
        // merged index write +
        // copy merged index to vault +
        // save merged index as last local index +
        // upload synchronization list +
        // release lock
        $progressionListener->start(count($operationCollection) + 5);

        foreach ($operationCollection as $operation)
        {
            /** @var OperationInterface $operation */

            $success = $operation->execute();

            $operationResult = new OperationResult($operation, $success);
            $operationResultCollection->addOperationResult($operationResult);

            $progressionListener->advance();
        }

        // dump new index
        $mergedIndexFilePath = $this->writeIndexToTemporaryFile($mergedIndex);

        $progressionListener->advance();

        // upload new index
        $readStream = fopen($mergedIndexFilePath, 'rb');
        $compressionFilter = stream_filter_append($readStream, 'zlib.deflate');
        $this->vaultConnection->writeStream($synchronization->getBlobId(), $readStream);
        rewind($readStream);
        stream_filter_remove($compressionFilter);

        $progressionListener->advance();

        // save new index locally
        $writeStream = fopen($this->getLastLocalIndexFilePath(), 'wb');
        stream_copy_to_stream($readStream, $writeStream);
        fclose($writeStream);
        fclose($readStream);

        $progressionListener->advance();

        // upload new synchronization list
        $synchronizationListFilePath = $this->writeSynchronizationListToTemporaryFile($synchronizationList);
        $readStream = fopen($synchronizationListFilePath, 'rb');
        stream_filter_append($readStream, 'zlib.deflate');
        $this->vaultConnection->writeStream(static::SYNCHRONIZATION_LIST_FILE_NAME, $readStream);
        fclose($readStream);

        // release lock
        if (!$this->getLockAdapter()->releaseLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to release lock.');
        }

        $progressionListener->advance();
        $progressionListener->finish();

        return $operationResultCollection;
    }

    /**
     * Loads and returns the list of synchronizations from the vault.
     *
     * @return SynchronizationList
     */
    public function loadSynchronizationList()
    {
        $list = null;

        if ($this->vaultConnection->exists(static::SYNCHRONIZATION_LIST_FILE_NAME))
        {
            $stream = $this->vaultConnection->getReadStream(static::SYNCHRONIZATION_LIST_FILE_NAME);

            stream_filter_append($stream, 'zlib.inflate');

            $list = $this->readSynchronizationListFromStream($stream);

            fclose($stream);
        }

        return $list;
    }

    protected function doLoadRemoteIndex(int $revision, SynchronizationList $synchronizationList = null)
    {
        if ($synchronizationList === null)
        {
            $synchronizationList = $this->loadSynchronizationList();
        }

        $synchronization = $synchronizationList->getSynchronizationByRevision($revision);

        if (!$synchronization)
        {
            return null;
        }

        $index = null;

        if ($this->vaultConnection->exists($synchronization->getBlobId()))
        {
            $stream = $this->vaultConnection->getReadStream($synchronization->getBlobId());

            stream_filter_append($stream, 'zlib.inflate');

            $index = $this->readIndexFromStream($stream);

            fclose($stream);
        }

        return $index;
    }

    protected function doBuildMergedIndex(Index $localIndex = null, Index $lastLocalIndex = null, Index $remoteIndex = null)
    {
        $localIndex = $localIndex ?: $this->buildLocalIndex();
        $lastLocalIndex = $lastLocalIndex ?: $this->loadLastLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->loadRemoteIndex();

        return $this->getIndexMerger()->merge($localIndex, $lastLocalIndex, $remoteIndex);
    }

    protected function doGetOperationCollection(Index $localIndex = null, Index $remoteIndex = null, Index $mergedIndex = null): OperationCollection
    {
        $localIndex = $localIndex ?: $this->buildLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->loadRemoteIndex();
        $mergedIndex = $mergedIndex ?: $this->doBuildMergedIndex($localIndex, $remoteIndex);

        $uploadStreamFilters = [
            'zlib.deflate' => []
        ];
        $downloadStreamFilters = [
            'zlib.inflate' => []
        ];


        $operationCollection = new OperationCollection();

        // mtimes to be set for directories are collected and applied afterwards as they get modified by synchronization operations as well
        $directoryMtimes = [];

        // relies on the directory tree structure being traversed in pre-order (or at least a directory appears before its content)
        foreach ($mergedIndex as $mergedIndexObject)
        {
            /** @var IndexObject $mergedIndexObject */

            $absoluteLocalPath = $this->localPath . $mergedIndexObject->getRelativePath();

            $localObject = $localIndex->getObjectByPath($mergedIndexObject->getRelativePath());
            $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($mergedIndexObject->getRelativePath()) : null;

            // unlink to-be-overridden local path with different type
            if ($localObject !== null && $localObject->getType() !== $mergedIndexObject->getType())
            {
                $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
            }


            if ($mergedIndexObject->isDirectory())
            {
                if ($localObject === null || !$localObject->isDirectory())
                {
                    $operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $mergedIndexObject->getMode()));
                }

                if ($localObject !== null && $localObject->isDirectory())
                {
                    if ($localObject->getMtime() !== $mergedIndexObject->getMtime())
                    {
                        $directoryMtimes[$absoluteLocalPath] = $mergedIndexObject->getMtime();
                    }
                }
            }

            elseif ($mergedIndexObject->isFile())
            {
                // local file did not exist, hasn't been a file before or is outdated
                if ($localObject === null || !$localObject->isFile() || $localObject->getMtime() < $mergedIndexObject->getMtime())
                {
                    $operationCollection->addOperation(new DownloadOperation($absoluteLocalPath, $mergedIndexObject->getBlobId(), $this->vaultConnection, $downloadStreamFilters));
                    $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mergedIndexObject->getMtime()));
                    $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $mergedIndexObject->getMode()));

                    $directoryMtimes[dirname($absoluteLocalPath)] = $mergedIndexObject->getMtime();
                }

                // local file got created or updated
                elseif ($remoteObject === null || $mergedIndexObject->getBlobId() !== $remoteObject->getBlobId())
                {
                    // generate blob id
                    do
                    {
                        $newBlobId = $mergedIndex->generateNewBlobId();
                    }
                    while ($this->vaultConnection->exists($newBlobId));

                    $mergedIndexObject->setBlobId($newBlobId);

                    $operationCollection->addOperation(new UploadOperation($absoluteLocalPath, $mergedIndexObject->getBlobId(), $this->vaultConnection, $uploadStreamFilters));
                }
            }

            elseif ($mergedIndexObject->isLink())
            {
                $absoluteLinkTarget = dirname($absoluteLocalPath) . DIRECTORY_SEPARATOR . $mergedIndexObject->getLinkTarget();

                if ($localObject !== null && $localObject->getLinkTarget() !== $mergedIndexObject->getLinkTarget())
                {
                    $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
                    $operationCollection->addOperation(new SymlinkOperation($absoluteLocalPath, $absoluteLinkTarget, $mergedIndexObject->getMode()));
                }
            }

            else
            {
                // unknown/invalid object type
                throw new Exception();
            }


            if ($localObject !== null && $localObject->getMode() !== $mergedIndexObject->getMode())
            {
                $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $mergedIndexObject->getMode()));
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $operationCollection->addOperation(new UnlinkOperation($this->localPath . $localObject->getRelativePath()));
            }
        }

        // set directory mtimes after all other modifications have been performed
        foreach ($directoryMtimes as $absoluteLocalPath => $mtime)
        {
            $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mtime));
        }

        return $operationCollection;
    }

    protected function readIndexFromStream($stream, \DateTime $created = null): Index
    {
        if (!is_resource($stream))
        {
            throw new Exception();
        }

        $index = new Index($created);

        while (($row = fgetcsv($stream)) !== false)
        {
            $index->addObject(IndexObject::fromIndexRecord($row));
        }

        return $index;
    }

    protected function writeIndexToTemporaryFile(Index $index): string
    {
        $path = tempnam(sys_get_temp_dir(), 'index');
        $stream = fopen($path, 'wb');

        foreach ($index as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $object->getIndexRecord()) === false)
            {
                throw new Exception();
            }
        }

        fclose($stream);

        return $path;
    }

    protected function readSynchronizationListFromStream($stream): SynchronizationList
    {
        if (!is_resource($stream))
        {
            throw new Exception();
        }

        $list = new SynchronizationList();

        while (($row = fgetcsv($stream)) !== false)
        {
            $list->addSynchronization(Synchronization::fromRecord($row));
        }

        return $list;
    }

    protected function writeSynchronizationListToTemporaryFile(SynchronizationList $synchronizationList): string
    {
        $path = tempnam(sys_get_temp_dir(), 'synchronizationList');
        $stream = fopen($path, 'wb');

        foreach ($synchronizationList as $synchronization)
        {
            /** @var Synchronization $synchronization */

            if (fputcsv($stream, $synchronization->getRecord()) === false)
            {
                throw new Exception();
            }
        }

        fclose($stream);

        return $path;
    }

    protected function generateNewBlobId(): string
    {
        do
        {
            $blobId = Uuid::uuid4()->toString();
        }
        while ($this->vaultConnection->exists($blobId));

        return $blobId;
    }

    protected function initMetadataDirectory(): string
    {
        $path = $this->localPath . static::METADATA_DIRECTORY_NAME;

        if (!is_dir($path))
        {
            if (!mkdir($path))
            {
                throw new Exception();
            }
        }

        return $path . DIRECTORY_SEPARATOR;
    }

    protected function getLastLocalIndexFilePath(): string
    {
        return $this->initMetadataDirectory() . sprintf('lastLocalIndex-%s', $this->getTitle());
    }
}
