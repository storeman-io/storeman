<?php

namespace Archivr\ConflictHandler;

use Archivr\IndexObject;

/**
 * This conflict handler always prefers the remote change.
 */
class PreferRemoteConflictHandler implements ConflictHandlerInterface
{
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int
    {
        return ConflictHandlerInterface::USE_REMOTE;
    }
}
