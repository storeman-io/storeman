<?php

namespace Storeman\VaultLayout;

use Storeman\FileReader;
use Storeman\Synchronization;
use Storeman\SynchronizationList;

/**
 * The vault layout defines the data layout within a vault. As this the vault layout is the component that needs to
 * support features like encryption, de-duplication and other functionality that can be abstracted by this interface.
 */
interface VaultLayoutInterface
{
    /**
     * Returns list of synchronizations
     *
     * @return SynchronizationList
     */
    public function getSynchronizations(): SynchronizationList;

    /**
     * Returns the last synchronization to this vault.
     *
     * @return Synchronization
     */
    public function getLastSynchronization(): ?Synchronization;

    /**
     * Returns synchronization by given revision.
     *
     * @param int $revision
     * @return Synchronization
     */
    public function getSynchronization(int $revision): Synchronization;

    /**
     * Returns a stream from which the content of a given blob id can be read.
     *
     * @param string $blobId
     * @return resource
     */
    public function readBlob(string $blobId);

    /**
     * Writes a new synchronization into the vault.
     *
     * @param Synchronization $synchronization
     * @param FileReader $fileReader
     */
    public function writeSynchronization(Synchronization $synchronization, FileReader $fileReader);
}
