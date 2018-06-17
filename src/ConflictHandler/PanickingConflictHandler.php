<?php

namespace Storeman\ConflictHandler;

use Storeman\Exception\ConflictException;
use Storeman\Index\IndexObject;

class PanickingConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int
    {
        throw new ConflictException("Occurred conflict on {$remoteObject->getRelativePath()}");
    }
}
