<?php

namespace Archivr\SynchronizationProgressListener;

class DummySynchronizationProgressListener implements SynchronizationProgressListenerInterface
{
    public function start(int $stepCount)
    {
        // nop
    }

    public function advance(int $steps = 1)
    {
        // nop
    }

    public function finish()
    {
        // nop
    }
}
