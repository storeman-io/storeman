<?php

namespace Archivr;

use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\ConnectionAdapter\StreamConnectionAdapter;
use Archivr\Exception\ConfigurationException;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterInterface;

class ArchivR
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var Vault[]
     */
    protected $vaults = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getConnection(string $vaultTitle): ConnectionAdapterInterface
    {
        $connectionConfiguration = $this->configuration->getConnectionConfigurationByTitle($vaultTitle);

        if ($connectionConfiguration === null)
        {
            throw new \InvalidArgumentException(sprintf('Unknown connection title: %s.', $vaultTitle));
        }

        // todo: replace by usage of factory map
        switch ($connectionConfiguration->getAdapter())
        {
            case 'path':

                $connection = new StreamConnectionAdapter($connectionConfiguration['path']);

                break;

            default:

                throw new ConfigurationException(sprintf('Unknown connection adapter: %s.', $connectionConfiguration->getAdapter()));
        }

        return $connection;
    }

    public function getLockAdapter(string $vaultTitle): LockAdapterInterface
    {
        $connectionConfiguration = $this->configuration->getConnectionConfigurationByTitle($vaultTitle);

        if ($connectionConfiguration === null)
        {
            throw new \InvalidArgumentException(sprintf('Unknown connection title: %s.', $vaultTitle));
        }

        // todo: replace by usage of factory map
        switch ($connectionConfiguration->getAdapter())
        {
            default:

                $lockAdapter = new ConnectionBasedLockAdapter($this->getConnection($vaultTitle));
        }

        return $lockAdapter;
    }

    public function buildOperationCollection(): OperationCollection
    {
        $return = new OperationCollection();

        foreach ($this->getVaults() as $vault)
        {
            $return->append($vault->getOperationCollection());
        }

        return $return;
    }

    /**
     * @return Vault[]
     */
    public function getVaults(): array
    {
        return array_map(function(ConnectionConfiguration $connectionConfiguration) {

            return $this->getVault($connectionConfiguration->getTitle());

        }, $this->configuration->getConnectionConfigurations());
    }

    public function getVault(string $vaultTitle): Vault
    {
        if (!isset($this->vaults[$vaultTitle]))
        {
            $vault = new Vault(
                $this->configuration->getLocalPath(),
                $this->getConnection($vaultTitle)
            );
            $vault->setLockAdapter($this->getLockAdapter($vaultTitle));

            $this->vaults[$vaultTitle] = $vault;
        }

        return $this->vaults[$vaultTitle];
    }

    public function synchronize(SynchronizationProgressListenerInterface $progressionListener = null): OperationResultCollection
    {
        $return = new OperationResultCollection();

        foreach ($this->getVaults() as $vault)
        {
            $return->append($vault->synchronize($progressionListener));
        }

        return $return;
    }
}
