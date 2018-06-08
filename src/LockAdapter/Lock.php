<?php

namespace Storeman\LockAdapter;

class Lock
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $identity;

    /**
     * @var \DateTime
     */
    protected $acquired;

    public function __construct(string $name, string $identity = null, \DateTime $acquired = null)
    {
        $this->name = $name;
        $this->identity = $identity;
        $this->acquired = $acquired ?: new \DateTime();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->identity;
    }

    /**
     * @return \DateTime
     */
    public function getAcquired(): \DateTime
    {
        return $this->acquired;
    }

    public function getPayload(): string
    {
        return json_encode([
            'name' => $this->name,
            'identity' => $this->identity,
            'acquired' => $this->acquired->getTimestamp()
        ]);
    }

    public static function fromPayload(string $payload): Lock
    {
        $info = json_decode($payload, true);

        return new static(
            $info['name'],
            $info['identity'],
            new \DateTime("@{$info['acquired']}")
        );
    }
}
