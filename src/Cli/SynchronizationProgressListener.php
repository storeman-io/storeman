<?php

namespace Storeman\Cli;

use Storeman\SynchronizationProgressListener\SynchronizationProgressListenerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapter for the Symfony ProgressBar console helper implementing the synchronization progress listener interface.
 */
class SynchronizationProgressListener implements SynchronizationProgressListenerInterface
{
    /**
     * @var ProgressBar
     */
    protected $progress;

    public function __construct(OutputInterface $output)
    {
        $this->progress = new ProgressBar($output);
    }

    public function start(int $stepCount)
    {
        $this->progress->start($stepCount);
    }

    public function advance()
    {
        $this->progress->advance();
    }

    public function finish()
    {
        $this->progress->finish();
    }
}
