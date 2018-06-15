<?php

namespace Storeman;

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

    public function toScalarArray(): array
    {
        return [
            $this->revision,
            $this->time->getTimestamp(),
            $this->identity
        ];
    }

    public static function fromScalarArray(array $array, Index $index = null): Synchronization
    {
        $instance = new static(
            $array[0],
            \DateTime::createFromFormat('U', $array[1]),
            $array[2],
            $index
        );

        return $instance;
    }
}
