<?php

namespace Archivr\IndexMerger;

use Archivr\Index;

interface IndexMergerInterface
{
    /**
     * @param Index $localIndex
     * @param Index $lastLocalIndex
     * @param Index $remoteIndex
     *
     * @return Index
     */
    public function merge(Index $localIndex, Index $lastLocalIndex = null, Index $remoteIndex = null): Index;
}