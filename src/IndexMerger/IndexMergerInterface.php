<?php

namespace Archivr\IndexMerger;

use Archivr\ConflictHandler\ConflictHandlerInterface;
use Archivr\Index;

interface IndexMergerInterface
{
    /**
     * Injects the conflict handler to be used which is required if a conflict that cannot be resolved automatically is
     * occurred.
     *
     * @param ConflictHandlerInterface $conflictHandler
     * @return IndexMergerInterface
     */
    public function setConflictHandler(ConflictHandlerInterface $conflictHandler = null): IndexMergerInterface;

    /**
     * @return ConflictHandlerInterface
     */
    public function getConflictHandler(): ConflictHandlerInterface;

    /**
     * Merges the given remote, local and lastLocal indices into a "merged" index representing the new state of the
     * vault.
     *
     * @param Index $remoteIndex
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     *
     * @return Index
     */
    public function merge(Index $remoteIndex, Index $localIndex, Index $lastLocalIndex = null): Index;
}
