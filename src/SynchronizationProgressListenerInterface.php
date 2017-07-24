<?php

namespace Archivr;

interface SynchronizationProgressListenerInterface
{
    /**
     * Is called once when the synchronization is started.
     *
     * @param int $stepCount Total step counts that will be executed.
     */
    public function start(int $stepCount);

    /**
     * Is called for every step that is completed.
     */
    public function advance();

    /**
     * Is called once when the synchronization is finished.
     */
    public function finish();
}