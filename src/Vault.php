<?php

namespace Storeman;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Storeman\Config\VaultConfiguration;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Hash\HashContainer;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\Operation\WriteSynchronizationOperation;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\IndexMerger\IndexMergerInterface;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\OperationListBuilder\OperationListBuilderInterface;
use Storeman\SynchronizationProgressListener\DummySynchronizationProgressListener;
use Storeman\SynchronizationProgressListener\SynchronizationProgressListenerInterface;
use Storeman\VaultLayout\VaultLayoutInterface;

/**
 * Represents a vault in a set of vaults associated to a storeman (archive) instance.
 */
class Vault implements LoggerAwareInterface
{
    use LoggerAwareTrait;


    public const LOCK_SYNC = 'sync';


    /**
     * @var Storeman
     */
    protected $storeman;

    /**
     * @var VaultConfiguration
     */
    protected $vaultConfiguration;

    /**
     * @var VaultLayoutInterface
     */
    protected $vaultLayout;

    /**
     * @var StorageAdapterInterface
     */
    protected $storageAdapter;

    /**
     * @var LockAdapterInterface
     */
    protected $lockAdapter;

    /**
     * @var IndexMergerInterface
     */
    protected $indexMerger;

    /**
     * @var ConflictHandlerInterface
     */
    protected $conflictHandler;

    /**
     * @var OperationListBuilderInterface
     */
    protected $operationListBuilder;

    /**
     * @var Index
     */
    protected $lastLocalIndex;

    public function __construct(Storeman $storeman, VaultConfiguration $vaultConfiguration)
    {
        $this->storeman = $storeman;
        $this->vaultConfiguration = $vaultConfiguration;
        $this->logger = new NullLogger();
    }

    public function getStoreman(): Storeman
    {
        return $this->storeman;
    }

    public function getVaultConfiguration(): VaultConfiguration
    {
        return $this->vaultConfiguration;
    }

    public function getVaultLayout(): VaultLayoutInterface
    {
        return $this->vaultLayout ?: ($this->vaultLayout = $this->getContainer()->get('vaultLayout'));
    }

    public function getStorageAdapter(): StorageAdapterInterface
    {
        return $this->storageAdapter ?: ($this->storageAdapter = $this->getContainer()->get('storageAdapter'));
    }

    public function getLockAdapter(): LockAdapterInterface
    {
        return $this->lockAdapter ?: ($this->lockAdapter = $this->getContainer()->get('lockAdapter'));
    }

    public function getIndexMerger(): IndexMergerInterface
    {
        return $this->indexMerger ?: ($this->indexMerger = $this->getContainer()->get('indexMerger'));
    }

    public function getConflictHandler(): ConflictHandlerInterface
    {
        return $this->conflictHandler ?: ($this->conflictHandler = $this->getContainer()->get('conflictHandler'));
    }

    public function getOperationListBuilder(): OperationListBuilderInterface
    {
        return $this->operationListBuilder ?: ($this->operationListBuilder = $this->getContainer()->get('operationListBuilder'));
    }

    /**
     * Reads and returns the index representing the local state on the last synchronization.
     *
     * @return Index
     * @throws Exception
     */
    public function getLastLocalIndex(): ?Index
    {
        if ($this->lastLocalIndex === null)
        {
            $index = null;
            $path = $this->getLastLocalIndexFilePath();

            if (is_file($path))
            {
                $this->logger->info("Reading in last local index from {$path}...");

                $stream = fopen($path, 'rb');

                $index = new Index();
                while (($row = fgetcsv($stream)) !== false)
                {
                    $index->addObject($this->createIndexObjectFromScalarArray($row));
                }

                fclose($stream);

                $this->logger->info("Read {$index->count()} records for last local index");
            }
            else
            {
                $this->logger->info("No last local index exists");
            }

            $this->lastLocalIndex = $index;
        }

        return $this->lastLocalIndex;
    }

    /**
     * Reads and returns the current remote index.
     *
     * @param int $revision Revision to load. Defaults to the last revision.
     *
     * @return Index
     */
    public function getRemoteIndex(int $revision = null): ?Index
    {
        $this->logger->info(sprintf("Loading %s remote index...", $revision ? "r{$revision}" : 'latest'));

        $synchronization = $revision ?
            $this->getVaultLayout()->getSynchronization($revision) :
            $this->getVaultLayout()->getLastSynchronization();

        return $synchronization ? $synchronization->getIndex() : null;
    }

    /**
     * Computes and returns the index representing the vault state after the local index has been merged with the remote index.
     *
     * @return Index
     */
    public function getMergedIndex(): Index
    {
        return $this->doBuildMergedIndex();
    }

    /**
     * Synchronizes the local with the remote state by executing all operations returned by getOperationList()
     *
     * @param int $newRevision
     * @param SynchronizationProgressListenerInterface $progressionListener
     *
     * @return OperationResultList
     * @throws Exception
     */
    public function synchronize(int $newRevision = null, SynchronizationProgressListenerInterface $progressionListener = null): OperationResultList
    {
        $progressionListener = $progressionListener ?: new DummySynchronizationProgressListener();

        $localIndex = $this->storeman->getLocalIndex();
        $lastLocalIndex = $this->getLastLocalIndex();


        if (!$this->getLockAdapter()->acquireLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to acquire lock.');
        }


        $lastSynchronization = $this->getVaultLayout()->getLastSynchronization();

        if ($lastSynchronization)
        {
            $newRevision = $newRevision ?: ($lastSynchronization->getRevision() + 1);
            $remoteIndex = $lastSynchronization->getIndex();
        }
        else
        {
            $newRevision = $newRevision ?: 1;
            $remoteIndex = null;
        }

        // compute merged index
        $mergedIndex = $this->doBuildMergedIndex($localIndex, $lastLocalIndex, $remoteIndex);

        $synchronization = new Synchronization($newRevision, new \DateTime(), $this->storeman->getConfiguration()->getIdentity(), $mergedIndex);

        $operationList = $this->getOperationListBuilder()->buildOperationList($mergedIndex, $localIndex);
        $operationList->add(new OperationListItem(new WriteSynchronizationOperation($synchronization)));

        $operationResultList = $this->executeOperationList($operationList, $this->storeman->getConfiguration()->getPath(), $progressionListener);

        // release lock
        if (!$this->getLockAdapter()->releaseLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to release lock.');
        }

        // save merged index locally
        $this->writeLastLocalIndex($mergedIndex);

        return $operationResultList;
    }

    /**
     * Restores the local state at the given revision from the vault.
     *
     * @param int $revision
     * @param SynchronizationProgressListenerInterface $progressionListener
     *
     * @return OperationResultList
     * @throws Exception
     */
    public function restore(int $revision = null, SynchronizationProgressListenerInterface $progressionListener = null): OperationResultList
    {
        return $this->doRestore($revision, $progressionListener);
    }

    /**
     * @param string $targetPath
     * @param int $revision
     * @param SynchronizationProgressListenerInterface|null $progressListener
     *
     * @return OperationResultList
     * @throws \Exception
     */
    public function dump(string $targetPath, int $revision = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        return $this->doRestore($revision, $progressListener, true, $targetPath);
    }

    /**
     * Returns a hash that is the same for any vault referencing the same physical storage location.
     *
     * @return string
     */
    public function getHash(): string
    {
        return hash('sha1', implode([
            get_class($this->getStorageAdapter()),
            $this->getStorageAdapter()->getIdentificationString($this->vaultConfiguration),
        ]));
    }

    /**
     * Returns an identifier usable for UI.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return "{$this->getHash()} ({$this->vaultConfiguration->getTitle()})";
    }

    protected function doBuildMergedIndex(Index $localIndex = null, Index $lastLocalIndex = null, Index $remoteIndex = null): Index
    {
        $localIndex = $localIndex ?: $this->storeman->getLocalIndex();
        $lastLocalIndex = $lastLocalIndex ?: $this->getLastLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->getRemoteIndex();

        if ($remoteIndex === null)
        {
            return $localIndex;
        }

        return $this->getIndexMerger()->merge($this->getConflictHandler(), $remoteIndex, $localIndex, $lastLocalIndex, IndexMergerInterface::INJECT_BLOBID);
    }

    protected function doRestore(int $revision = null, SynchronizationProgressListenerInterface $progressionListener = null, bool $skipLastLocalIndexUpdate = false, string $targetPath = null): OperationResultList
    {
        $progressionListener = $progressionListener ?: new DummySynchronizationProgressListener();
        $targetPath = $targetPath ?: $this->storeman->getConfiguration()->getPath();

        $localIndex = $this->storeman->getLocalIndex($targetPath);

        if (!$this->getLockAdapter()->acquireLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to acquire lock.');
        }

        // fall back to last revision
        if ($revision === null)
        {
            $lastSynchronization = $this->getVaultLayout()->getLastSynchronization();

            if (!$lastSynchronization)
            {
                throw new Exception('No revision to restore from.');
            }

            $revision = $lastSynchronization->getRevision();
        }

        $remoteIndex = $this->getRemoteIndex($revision);

        if ($remoteIndex === null)
        {
            throw new Exception("Unknown revision: {$revision}");
        }

        $operationList = $this->getOperationListBuilder()->buildOperationList($remoteIndex, $localIndex);

        $operationResultList = $this->executeOperationList($operationList, $targetPath, $progressionListener);

        if (!$this->getLockAdapter()->releaseLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to release lock.');
        }

        if (!$skipLastLocalIndexUpdate)
        {
            $this->writeLastLocalIndex($remoteIndex);
        }

        return $operationResultList;
    }

    protected function executeOperationList(OperationList $operationList, string $basePath, SynchronizationProgressListenerInterface $progressionListener): OperationResultList
    {
        $this->logger->notice(sprintf("Executing %d operation(s)...", count($operationList)));

        $operationResultList = new OperationResultList();

        $progressionListener->start(count($operationList));
        foreach ($operationList as $index => $operationListItem)
        {
            /** @var OperationListItem $operationListItem */

            $this->logger->debug(sprintf("#%d {$operationListItem->getOperation()}", $index + 1));

            $success = $operationListItem->getOperation()->execute($basePath, $this->storeman->getFileReader(), $this->getVaultLayout());

            // update index ctime right after operation execution to make it as reliable as possible for usage in the next run
            if ($success && $indexObject = $operationListItem->getIndexObject())
            {
                $absolutePath = PathUtils::getAbsolutePath($basePath . $indexObject->getRelativePath());

                if (is_file($absolutePath))
                {
                    $indexObject->setCtime(FilesystemUtility::lstat($absolutePath)['ctime']);
                }
            }

            $operationResult = new OperationResult($operationListItem->getOperation(), $success);
            $operationResultList->addOperationResult($operationResult);

            $progressionListener->advance();
        }
        $progressionListener->finish();

        return $operationResultList;
    }

    protected function writeLastLocalIndex(Index $index): void
    {
        $this->logger->info(sprintf("Writing last local index with %d records to %s", $index->count(), $this->getLastLocalIndexFilePath()));

        // prevent outdated cache on failure
        $this->lastLocalIndex = null;

        $stream = fopen($this->getLastLocalIndexFilePath(), 'wb');

        foreach ($index as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $this->indexObjectToScalarArray($object)) === false)
            {
                throw new Exception("Writing to {$this->getLastLocalIndexFilePath()} failed");
            }
        }

        fclose($stream);

        // update local cache
        $this->lastLocalIndex = $index;
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
            sprintf('%.9f', $indexObject->getMtime()),
            sprintf('%.9f', $indexObject->getCtime()),
            $indexObject->getPermissions(),
            $indexObject->getSize(),
            $indexObject->getInode(),
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
            (float)$array[2],
            (float)$array[3],
            (int)$array[4],
            ($array[5] !== '') ? (int)$array[5] : null,
            (int)$array[6],
            $array[7] ?: null,
            $array[8] ?: null,
            $array[9] ? (new HashContainer())->unserialize($array[9]) : null
        );
    }

    protected function getLastLocalIndexFilePath(): string
    {
        return $this->storeman->getMetadataDirectoryPath() . sprintf('lastLocalIndex-%s', $this->getHash());
    }

    /**
     * Returns the service container with this vault as its context.
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->storeman->getContainer($this);
    }
}
