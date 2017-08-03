<?php

namespace Archivr;

use Archivr\Connection\ConnectionInterface;
use Archivr\Connection\StreamConnection;
use Archivr\Exception\ConfigurationException;

class ArchivR
{
    /**
     * @var Configuration
     */
    protected $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getConnection(string $title): ConnectionInterface
    {
        $connectionConfiguration = $this->configuration->getConnectionConfigurationByTitle($title);

        if ($connectionConfiguration === null)
        {
            throw new \InvalidArgumentException(sprintf('Unknown connection title: %s.', $title));
        }

        switch ($connectionConfiguration->getAdapter())
        {
            case 'path':

                $connection = new StreamConnection($connectionConfiguration['path']);

                break;

            default:

                throw new ConfigurationException(sprintf('Unknown connection adapter: %s.', $connectionConfiguration->getAdapter()));
        }

        return $connection;
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
     * @return VaultInterface[]
     */
    public function getVaults(): array
    {
        $vaults = [];

        foreach ($this->configuration->getConnectionConfigurations() as $connectionConfiguration)
        {
            $vaults[] = $this->doGetVault($this->getConnection($connectionConfiguration->getTitle()));
        }

        return $vaults;
    }

    public function getVault(string $title): VaultInterface
    {
        return $this->doGetVault($this->getConnection($title));
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

    protected function doGetVault(ConnectionInterface $connection): VaultInterface
    {
        return new Vault($connection, $this->configuration->getLocalPath());
    }
}
