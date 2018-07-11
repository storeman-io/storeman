<?php

namespace Storeman\ConflictHandler;

use Storeman\Index\IndexObject;

interface ConflictHandlerInterface
{
    public const USE_LOCAL = 1;
    public const USE_REMOTE = 2;


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
    public function handleConflict(IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): int;
}
