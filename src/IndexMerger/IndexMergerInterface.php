<?php

namespace Storeman\IndexMerger;

use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\Index;

interface IndexMergerInterface
{
    public const INJECT_BLOBID = 1;

    /**
     * Merges the given remote, local and lastLocal indices into a "merged" index representing the new state of the
     * vault.
     *
     * Options:
     *
     * INJECT_BLOBID - If set an eventually already existing blobId is injected into the given localIndex.
     *
     * @param ConflictHandlerInterface $conflictHandler
     * @param Index $remoteIndex
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     * @param int $options
     *
     * @return Index
     */
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, ?Index $lastLocalIndex, int $options = 0): Index;
}
