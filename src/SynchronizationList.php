<?php

namespace Archivr;

use Archivr\Exception\Exception;

class SynchronizationList implements \Countable, \IteratorAggregate
{
    /**
     * Chronologically ordered list of synchronizations (earliest to latest).
     *
     * @var Synchronization[]
     */
    protected $synchronizations = [];

    /**
     * @param Synchronization[] $synchronizations
     */
    public function __construct(array $synchronizations = [])
    {
        foreach ($synchronizations as $synchronization)
        {
            $this->addSynchronization($synchronization);
        }
    }

    /**
     * @param Synchronization $synchronization
     *
     * @return SynchronizationList
     * @throws Exception
     */
    public function addSynchronization(Synchronization $synchronization): SynchronizationList
    {
        if (isset($this->synchronizations[$synchronization->getRevision()]))
        {
            throw new Exception();
        }

        $this->synchronizations[$synchronization->getRevision()] = $synchronization;

        return $this;
    }

    /**
     * @return Synchronization
     */
    public function getLastSynchronization()
    {
        return empty($this->synchronizations) ? null : $this->synchronizations[count($this->synchronizations)];
    }

    /**
     * @param int $revision
     *
     * @return Synchronization
     */
    public function getSynchronizationByRevision(int $revision)
    {
        return isset($this->synchronizations[$revision]) ? $this->synchronizations[$revision] : null;
    }

    /**
     * Returns the synchronization that was the current one for the given date.
     *
     * @param \DateTime $time
     *
     * @return Synchronization
     */
    public function getSynchronizationByTime(\DateTime $time)
    {
        $current = null;

        foreach ($this->synchronizations as $synchronization)
        {
            if ($synchronization->getTime() > $time)
            {
                break;
            }

            $current = $synchronization;
        }

        return $current;
    }

    public function count(): int
    {
        return count($this->synchronizations);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->synchronizations);
    }
}
