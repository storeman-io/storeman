<?php

namespace Archivr;

use Archivr\Exception\Exception;

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
     * @var string
     */
    protected $blobId;

    public function __construct(int $revision, string $blobId, \DateTime $time, string $identity = null)
    {
        $this->revision = $revision;
        $this->blobId = $blobId;
        $this->time = $time;
        $this->identity = $identity;
    }

    /**
     * @return int
     */
    public function getRevision(): int
    {
        return $this->revision;
    }

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @return string
     */
    public function getBlobId(): string
    {
        return $this->blobId;
    }

    public function getRecord(): array
    {
        return [
            $this->revision,
            $this->blobId,
            $this->time->getTimestamp(),
            $this->identity
        ];
    }

    public static function fromRecord(array $row): Synchronization
    {
        $revision = $row[0];
        $blobId = $row[1];
        $time = \DateTime::createFromFormat('U', $row[2]);
        $identity = $row[3] ?: null;

        if (!($time instanceof \DateTime))
        {
            throw new Exception();
        }

        return new static($revision, $blobId, $time, $identity);
    }
}
