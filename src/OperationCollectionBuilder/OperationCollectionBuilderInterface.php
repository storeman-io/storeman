<?php

namespace Archivr\OperationCollectionBuilder;

use Archivr\Index;
use Archivr\OperationCollection;

interface OperationCollectionBuilderInterface
{
    public function buildOperationCollection(Index $mergedIndex, Index $localIndex, Index $remoteIndex = null): OperationCollection;
}
