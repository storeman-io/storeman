<?php

namespace Storeman\ConflictHandler;

use Storeman\IndexObject;

/**
 * This conflict handler always prefers the local change.
 */
class PreferLocalConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int
    {
        return ConflictHandlerInterface::USE_LOCAL;
    }
}
