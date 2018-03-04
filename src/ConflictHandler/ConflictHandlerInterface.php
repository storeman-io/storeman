<?php

namespace Archivr\ConflictHandler;

use Archivr\IndexObject;

interface ConflictHandlerInterface
{
    const USE_LOCAL = 1;
    const USE_REMOTE = 2;

    /**
     * Is called by the index merger if a conflict is occurred.
     *
     * Valid return values:
     *
     * USE_LOCAL - Prefer the local file.
     * USE_REMOTE - Prefer the remote file.
     *
     * @param IndexObject $remoteObject
     * @param IndexObject $localObject
     * @param IndexObject $lastLocalObject
     * @return int
     */
    public function handleConflict(IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): int;
}
