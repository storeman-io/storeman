<?php

namespace Archivr\OperationCollectionBuilder;

use Archivr\Index;
use Archivr\OperationCollection;

interface OperationCollectionBuilderInterface
{
    /**
     * This method is called after the index merger has mergerd the local, lastLocal and remote indices into a "merged"
     * index that represents the new state of the vault.
     *
     * Takes the local, remote and merged indices and has to return a ordered list of operations represented by
     * implementations of the Archivr\Operation\OperationInterface that have to be executed for both the local and
     * remote index equaling the merged index afterwards.
     *
     * @param Index $mergedIndex
     * @param Index $localIndex
     * @param Index|null $remoteIndex
     * @return OperationCollection
     */
    public function buildOperationCollection(Index $mergedIndex, Index $localIndex, Index $remoteIndex = null): OperationCollection;
}
