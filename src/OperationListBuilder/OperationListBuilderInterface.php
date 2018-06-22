<?php

namespace Storeman\OperationListBuilder;

use Storeman\Index\Index;
use Storeman\OperationList;

interface OperationListBuilderInterface
{
    /**
     * This method is called after the index merger has merged the local, lastLocal and remote indices into a "merged"
     * index that represents the new state of the vault.
     *
     * Takes the local and merged indices and has to return a ordered list of operations represented by
     * implementations of the Storeman\Operation\OperationInterface that have to be executed for the local state
     * equaling the merged index afterwards.
     *
     * @param Index $mergedIndex
     * @param Index $localIndex
     * @param Index|null $remoteIndex
     * @return OperationList
     */
    public function buildOperationList(Index $mergedIndex, Index $localIndex): OperationList;
}
