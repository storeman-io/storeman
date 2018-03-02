<?php

namespace Archivr\IndexMerger;

use Archivr\ConflictHandler\ConflictHandlerInterface;
use Archivr\Index;

interface IndexMergerInterface
{
    /**
     * @param ConflictHandlerInterface $conflictHandler
     * @return IndexMergerInterface
     */
    public function setConflictHandler(ConflictHandlerInterface $conflictHandler = null): IndexMergerInterface;

    /**
     * @return ConflictHandlerInterface
     */
    public function getConflictHandler(): ConflictHandlerInterface;

    /**
     * @param Index $remoteIndex
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     *
     * @return Index
     */
    public function merge(Index $remoteIndex, Index $localIndex, Index $lastLocalIndex = null): Index;
}
