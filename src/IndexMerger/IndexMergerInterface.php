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
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     * @param Index $remoteIndex
     *
     * @return Index
     */
    public function merge(Index $localIndex, Index $lastLocalIndex = null, Index $remoteIndex = null): Index;
}
