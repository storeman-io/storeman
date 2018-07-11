<?php

namespace Storeman\ConflictHandler;

use Storeman\Index\IndexObject;

/**
 * This conflict handler always prefers the remote change.
 */
class PreferRemoteConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): int
    {
        return ConflictHandlerInterface::USE_REMOTE;
    }
}
