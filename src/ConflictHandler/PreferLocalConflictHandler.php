<?php

namespace Storeman\ConflictHandler;

use Storeman\Index\IndexObject;

/**
 * This conflict handler always prefers the local change.
 */
class PreferLocalConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): int
    {
        return ConflictHandlerInterface::USE_LOCAL;
    }
}
