<?php

namespace Archivr\SynchronizationProgressListener;

use Archivr\SynchronizationProgressListener\SynchronizationProgressListenerInterface;

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
