<?php

namespace Archivr;

use Archivr\ConnectionAdapter\ConnectionAdapterFactoryContainer;
use Archivr\ConnectionAdapter\ConnectionAdapterInterface;
use Archivr\ConnectionAdapter\FlysystemConnectionAdapter;
use Archivr\Exception\ConfigurationException;
use Archivr\Exception\Exception;
use Archivr\LockAdapter\ConnectionBasedLockAdapter;
use Archivr\LockAdapter\LockAdapterFactoryContainer;
use Archivr\LockAdapter\LockAdapterInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ArchivR
{
    use TildeExpansionTrait;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var ConnectionAdapterFactoryContainer
     */
    protected $connectionAdapterFactoryContainer;

    /**
     * @var LockAdapterFactoryContainer
     */
    protected $lockAdapterFactoryContainer;

    /**
     * @var Vault[]
     */
    protected $vaults = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->connectionAdapterFactoryContainer = new ConnectionAdapterFactoryContainer([

            'path' => function(ConnectionConfiguration $connectionConfiguration)
            {
                $path = $connectionConfiguration->getSetting('path');
                $path = $this->expandTildePath($path);

                if (!is_dir($path) || !is_writable($path))
                {
                    throw new ConfigurationException(sprintf('Path "%s" does not exist or is not writable.', $path));
                }

                $adapter = new Local($path);
                $filesystem = new Filesystem($adapter);

                return new FlysystemConnectionAdapter($filesystem);
            },
        ]);
        $this->lockAdapterFactoryContainer = new LockAdapterFactoryContainer([

            'connection' => function(ConnectionConfiguration $connectionConfiguration)
            {
                $connection = $this->getConnection($connectionConfiguration->getTitle());

                return new ConnectionBasedLockAdapter($connection);
            }
        ]);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getConnectionAdapterFactoryContainer(): AbstractServiceFactoryContainer
    {
        return $this->connectionAdapterFactoryContainer;
    }

    public function getLockAdapterFactoryContainer(): AbstractServiceFactoryContainer
    {
        return $this->lockAdapterFactoryContainer;
    }

    public function getConnection(string $vaultTitle): ConnectionAdapterInterface
    {
        $connectionConfiguration = $this->configuration->getConnectionConfigurationByTitle($vaultTitle);

        if ($connectionConfiguration === null)
        {
            throw new Exception(sprintf('Unknown connection title: "%s".', $vaultTitle));
        }

        $connection = $this->connectionAdapterFactoryContainer->get($connectionConfiguration->getVaultAdapter(), $connectionConfiguration);

        if ($connection === null)
        {
            throw new ConfigurationException(sprintf('Unknown connection adapter: "%s".', $connectionConfiguration->getVaultAdapter()));
        }

        return $connection;
    }

    public function getLockAdapter(string $vaultTitle): LockAdapterInterface
    {
        $connectionConfiguration = $this->configuration->getConnectionConfigurationByTitle($vaultTitle);

        if ($connectionConfiguration === null)
        {
            throw new Exception(sprintf('Unknown connection title: "%s".', $vaultTitle));
        }

        $lockAdapter = $this->lockAdapterFactoryContainer->get($connectionConfiguration->getLockAdapter(), $connectionConfiguration);

        if ($lockAdapter === null)
        {
            throw new ConfigurationException(sprintf('Unknown lock adapter: "%s".', $connectionConfiguration->getLockAdapter()));
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
                $vaultTitle,
                $this->configuration->getLocalPath(),
                $this->getConnection($vaultTitle)
            );
            $vault->setLockAdapter($this->getLockAdapter($vaultTitle));
            $vault->setExclusions($this->configuration->getExclusions());

            $this->vaults[$vaultTitle] = $vault;
        }

        return $this->vaults[$vaultTitle];
    }

    public function synchronize(SynchronizationProgressListenerInterface $progressionListener = null): OperationResultCollection
    {
        $return = new OperationResultCollection();

        foreach ($this->getVaults() as $vault)
        {
            $lockAdapter = $vault->getLockAdapter();

            while (!$lockAdapter->acquireLock(Vault::LOCK_SYNC))
            {
                sleep(5);
            }

            $return->append($vault->synchronize($progressionListener));

            $lockAdapter->releaseLock(Vault::LOCK_SYNC);
        }

        return $return;
    }
}
