<?php

namespace Storeman\IndexMerger;

use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\Index;

interface IndexMergerInterface
{
    /**
     * Merges the given remote, local and lastLocal indices into a "merged" index representing the new state of the
     * vault.
     *
     * @param ConflictHandlerInterface $conflictHandler
     * @param Index $remoteIndex
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     *
     * @return Index
     */
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, Index $lastLocalIndex = null): Index;
}
