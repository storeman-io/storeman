<?php

namespace Archivr\ConflictHandler;

use Archivr\Exception\ConflictException;
use Archivr\IndexObject;

class PanickingConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int
    {
        throw new ConflictException("Occurred conflict on {$remoteObject->getRelativePath()}");
    }
}
