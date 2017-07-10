<?php

namespace Archivr;

interface VaultInterface
{
    /**
     * Construct the vault object based on a connection to the remote vault and its local representation.
     *
     * @param VaultConnectionInterface $vaultConnection
     * @param string $localPath
     */
    public function __construct(VaultConnectionInterface $vaultConnection, string $localPath);

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
     *
     * @return OperationCollection
     */
    public function getOperationCollection(): OperationCollection;

    /**
     * Generator function that yields instances of Archivr\OperationResult for each executed operation.
     *
     * @return OperationResult[]
     */
    public function synchronize();
}