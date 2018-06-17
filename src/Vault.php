<?php

namespace Storeman;

use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\IndexBuilder\IndexBuilderInterface;
use Storeman\Operation\WriteSynchronizationOperation;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Exception\Exception;
use Storeman\IndexMerger\IndexMergerInterface;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\OperationListBuilder\OperationListBuilderInterface;
use Storeman\SynchronizationProgressListener\DummySynchronizationProgressListener;
use Storeman\SynchronizationProgressListener\SynchronizationProgressListenerInterface;
use Storeman\VaultLayout\VaultLayoutInterface;
use Storeman\Operation\OperationInterface;

class Vault
{
    public const CONFIG_FILE_NAME = 'storeman.json';
    public const METADATA_DIRECTORY_NAME = '.storeman';
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
     * @var IndexBuilderInterface
     */
    protected $indexBuilder;

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

    public function __construct(Storeman $storeman, VaultConfiguration $vaultConfiguration)
    {
        $this->storeman = $storeman;
        $this->vaultConfiguration = $vaultConfiguration;
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

    public function getIndexBuilder(): IndexBuilderInterface
    {
        return $this->indexBuilder ?: ($this->indexBuilder = $this->getContainer()->get('indexBuilder'));
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
     * Builds and returns an index representing the current local state.
     *
     * @return Index
     */
    public function buildLocalIndex(): Index
    {
        return $this->getIndexBuilder()->buildIndexFromPath(
            $this->vaultConfiguration->getConfiguration()->getPath(),
            $this->getLocalIndexExclusionPatterns()
        );
    }

    /**
     * Reads and returns the index representing the local state on the last synchronization.
     *
     * @return Index
     * @throws Exception
     */
    public function loadLastLocalIndex(): ?Index
    {
        $index = null;
        $path = $this->getLastLocalIndexFilePath();

        if (is_file($path))
        {
            $stream = fopen($path, 'rb');

            $index = new Index();
            while (($row = fgetcsv($stream)) !== false)
            {
                $index->addObject(IndexObject::fromScalarArray($row));
            }

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
    public function loadRemoteIndex(int $revision = null): ?Index
    {
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
    public function buildMergedIndex(): Index
    {
        return $this->doBuildMergedIndex();
    }

    /**
     * Returns ordered list of operations required to synchronize the vault with the local path.
     * In addition to the object specific operations contained in the returned OperationList additional operations
     * might be necessary like index updates that do not belong to specific index objects.
     *
     * @return OperationList
     */
    public function getOperationList(): OperationList
    {
        $localIndex = $this->buildLocalIndex();
        $lastLocalIndex = $this->loadLastLocalIndex();
        $remoteIndex = $this->loadRemoteIndex();

        $mergedIndex = $this->doBuildMergedIndex($localIndex, $lastLocalIndex, $remoteIndex);

        return $this->getOperationListBuilder()->buildOperationList($mergedIndex, $localIndex, $remoteIndex);
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


        $synchronizationList = $this->loadSynchronizationList();
        $lastSynchronization = $synchronizationList->getLastSynchronization();

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

        $operationList = $this->getOperationListBuilder()->buildOperationList($mergedIndex, $localIndex, $remoteIndex);
        $operationList->addOperation(new WriteSynchronizationOperation($synchronization));

        $operationResultList = new OperationResultList();

        // operation count +
        // save merged index as last local index +
        // release lock
        $progressionListener->start(count($operationList) + 2);

        foreach ($operationList as $operation)
        {
            /** @var OperationInterface $operation */

            $success = $operation->execute($this->storeman->getConfiguration()->getPath(), $this->getVaultLayout());

            $operationResult = new OperationResult($operation, $success);
            $operationResultList->addOperationResult($operationResult);

            $progressionListener->advance();
        }

        // save merged index locally
        $this->writeLastLocalIndex($mergedIndex);
        $progressionListener->advance();

        // release lock
        if (!$this->getLockAdapter()->releaseLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to release lock.');
        }
        $progressionListener->advance();

        $progressionListener->finish();

        return $operationResultList;
    }

    /**
     * Loads and returns the list of synchronizations from the vault.
     *
     * @return SynchronizationList
     */
    public function loadSynchronizationList(): SynchronizationList
    {
        return $this->getVaultLayout()->getSynchronizations();
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

    protected function doBuildMergedIndex(Index $localIndex = null, Index $lastLocalIndex = null, Index $remoteIndex = null): Index
    {
        $localIndex = $localIndex ?: $this->buildLocalIndex();
        $lastLocalIndex = $lastLocalIndex ?: $this->loadLastLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->loadRemoteIndex();

        if ($remoteIndex === null)
        {
            return $localIndex;
        }

        return $this->getIndexMerger()->merge($this->getConflictHandler(), $remoteIndex, $localIndex, $lastLocalIndex);
    }

    protected function doRestore(int $revision = null, SynchronizationProgressListenerInterface $progressionListener = null, bool $skipLastLocalIndexUpdate = false, string $targetPath = null): OperationResultList
    {
        if ($progressionListener === null)
        {
            $progressionListener = new DummySynchronizationProgressListener();
        }

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

        $remoteIndex = $this->loadRemoteIndex($revision);

        if ($remoteIndex === null)
        {
            throw new Exception("Unknown revision: {$revision}");
        }

        $targetPath = $targetPath ?: $this->storeman->getConfiguration()->getPath();

        $localIndex = $this->getIndexBuilder()->buildIndexFromPath($targetPath, $this->getLocalIndexExclusionPatterns());

        $operationList = $this->getOperationListBuilder()->buildOperationList($remoteIndex, $localIndex, $remoteIndex);

        $operationResultList = new OperationResultList();

        // operation count +
        // save merged index as last local index +
        // release lock
        $progressionListener->start(count($operationList) + 2);

        foreach ($operationList as $operation)
        {
            /** @var OperationInterface $operation */

            $success = $operation->execute($targetPath, $this->getVaultLayout());

            $operationResult = new OperationResult($operation, $success);
            $operationResultList->addOperationResult($operationResult);

            $progressionListener->advance();
        }

        if (!$skipLastLocalIndexUpdate)
        {
            $this->writeLastLocalIndex($remoteIndex);
        }

        $progressionListener->advance();

        if (!$this->getLockAdapter()->releaseLock(static::LOCK_SYNC))
        {
            throw new Exception('Failed to release lock.');
        }

        $progressionListener->advance();
        $progressionListener->finish();

        return $operationResultList;
    }

    protected function writeLastLocalIndex(Index $index): void
    {
        $stream = fopen($this->getLastLocalIndexFilePath(), 'wb');

        foreach ($index as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $object->toScalarArray()) === false)
            {
                throw new Exception("Writing to {$this->getLastLocalIndexFilePath()} failed");
            }
        }

        fclose($stream);
    }

    protected function initMetadataDirectory(): string
    {
        $path = $this->storeman->getConfiguration()->getPath() . static::METADATA_DIRECTORY_NAME;

        if (!is_dir($path))
        {
            if (!mkdir($path))
            {
                throw new Exception("mkdir() failed for {$path}");
            }
        }

        return $path . DIRECTORY_SEPARATOR;
    }

    protected function getLastLocalIndexFilePath(): string
    {
        // todo: use other vault identifier
        return $this->initMetadataDirectory() . sprintf('lastLocalIndex-%s', $this->vaultConfiguration->getTitle());
    }

    /**
     * @return string[]
     */
    protected function getLocalIndexExclusionPatterns()
    {
        return array_merge($this->vaultConfiguration->getConfiguration()->getExclude(), [
            static::CONFIG_FILE_NAME,
            static::METADATA_DIRECTORY_NAME,
        ]);
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
