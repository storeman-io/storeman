<?php

namespace Archivr;

use Archivr\Connection\ConnectionInterface;
use Archivr\IndexMerger\IndexMergerInterface;

interface VaultInterface
{
    /**
     * Construct the vault object based on a connection to the remote vault and its local representation.
     *
     * @param ConnectionInterface $vaultConnection
     * @param string $localPath
     */
    public function __construct(ConnectionInterface $vaultConnection, string $localPath);

    /**
     * Sets the index merger to be used.
     *
     * @param IndexMergerInterface $indexMerger
     */
    public function setIndexMerger(IndexMergerInterface $indexMerger = null);

    /**
     * Returns the currently used index merger.
     *
     * @return IndexMergerInterface
     */
    public function getIndexMerger(): IndexMergerInterface;

    /**
     * Builds and returns an index representing the current local state.
     *
     * @return Index
     */
    public function buildLocalIndex(): Index;

    /**
     * Reads and returns the index representing the local state on the last synchronization.
     *
     * @return Index
     */
    public function loadLastLocalIndex();

    /**
     * Reads and returns the current remote index.
     *
     * @return Index
     */
    public function loadRemoteIndex();

    /**
     * Computes and returns the index representing the vault state after the local index has been merged with the remote index.
     *
     * @return Index
     */
    public function buildMergedIndex(): Index;

    /**
     * Returns ordered collection of operations required to synchronize the vault with the local path.
     * In addition to the object specific operations contained in the returned OperationCollection additional operations
     * might be necessary like index updates that do not belong to specific index objects.
     *
     * @return OperationCollection
     */
    public function getOperationCollection(): OperationCollection;

    /**
     * Synchronizes the local with the remote state by executing all operations returned by getOperationCollection() (broadly speaking).
     *
     * @param SynchronizationProgressListenerInterface $progressionListener
     *
     * @return OperationResultCollection
     */
    public function synchronize(SynchronizationProgressListenerInterface $progressionListener = null): OperationResultCollection;
}
