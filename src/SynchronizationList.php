<?php

namespace Storeman;

use Storeman\Exception\Exception;

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
     * Returns the synchronization with the highest revision.
     *
     * @return Synchronization
     */
    public function getLastSynchronization(): ?Synchronization
    {
        if ($this->synchronizations)
        {
            return $this->synchronizations[max(array_keys($this->synchronizations))];
        }

        return null;
    }

    /**
     * @param int $revision
     *
     * @return Synchronization
     */
    public function getSynchronization(int $revision): ?Synchronization
    {
        return isset($this->synchronizations[$revision]) ? $this->synchronizations[$revision] : null;
    }

    /**
     * Returns the synchronization that was the current one for the given datetime.
     *
     * @param \DateTime $time
     *
     * @return Synchronization
     */
    public function getSynchronizationByTime(\DateTime $time): ?Synchronization
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

    /**
     * Returns new synchronization list containing only those synchronizations that were performed by the given identity.
     *
     * @param string $identity
     * @return SynchronizationList
     */
    public function getSynchronizationsByIdentity(string $identity): SynchronizationList
    {
        return new static(array_filter(iterator_to_array($this->getIterator()), function(Synchronization $synchronization) use ($identity) {

            return $synchronization->getIdentity() === $identity;
        }));
    }

    /**
     * Returns set of revisions contained in this list.
     *
     * @return int[]
     */
    public function getRevisions(): array
    {
        return array_values(array_map(function(Synchronization $synchronization) {

            return $synchronization->getRevision();

        }, iterator_to_array($this->getIterator())));
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->synchronizations);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->synchronizations);
    }
}
