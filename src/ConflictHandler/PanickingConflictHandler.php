<?php

namespace Storeman\ConflictHandler;

use Storeman\Index\IndexObject;

class PanickingConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): int
    {
        throw new ConflictException("Occurred conflict on {$remoteObject->getRelativePath()}");
    }
}
