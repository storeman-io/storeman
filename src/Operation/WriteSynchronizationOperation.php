<?php

namespace Storeman\Operation;

use Storeman\Synchronization;
use Storeman\VaultLayout\VaultLayoutInterface;

class WriteSynchronizationOperation implements OperationInterface
{
    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(Synchronization $synchronization)
    {
        $this->synchronization = $synchronization;
    }

    public function execute(string $localBasePath, VaultLayoutInterface $vaultLayout): bool
    {
        $vaultLayout->writeSynchronization($this->synchronization);

        return true;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString(): string
    {
        return "Write synchronization with revision #{$this->synchronization->getRevision()}";
    }
}
