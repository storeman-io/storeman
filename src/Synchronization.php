<?php

namespace Storeman;

use Storeman\Index\Index;

/**
 * As the name suggests this class represents the synchronization for a specific revision.
 */
class Synchronization
{
    /**
     * @var int
     */
    protected $revision;

    /**
     * @var \DateTime
     */
    protected $time;

    /**
     * @var string
     */
    protected $identity;

    /**
     * @var Index
     */
    protected $index;

    public function __construct(int $revision, \DateTime $time, string $identity, Index $index = null)
    {
        $this->revision = $revision;
        $this->time = $time;
        $this->identity = $identity;
        $this->index = $index;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function getTime(): \DateTime
    {
        return $this->time;
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function getIndex(): ?Index
    {
        return $this->index;
    }

    public function setIndex(Index $index): Synchronization
    {
        assert($this->index === null);

        $this->index = $index;

        return $this;
    }
}
