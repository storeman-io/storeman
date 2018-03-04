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
use Archivr\SynchronizationProgressListener\SynchronizationProgressListenerInterface;
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

            'path' => function(VaultConfiguration $vaultConfiguration)
            {
                $path = $vaultConfiguration->getSetting('path');
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

            'connection' => function(VaultConfiguration $vaultConfiguration)
            {
                $connection = $this->getConnection($vaultConfiguration->getTitle());

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
        $vaultConfiguration = $this->configuration->getVaultConfigurationByTitle($vaultTitle);

        if ($vaultConfiguration === null)
        {
            throw new Exception(sprintf('Unknown vault title: "%s".', $vaultTitle));
        }

        $connection = $this->connectionAdapterFactoryContainer->get($vaultConfiguration->getVaultAdapter(), $vaultConfiguration);

        if ($connection === null)
        {
            throw new ConfigurationException(sprintf('Unknown connection adapter: "%s".', $vaultConfiguration->getVaultAdapter()));
        }

        return $connection;
    }

    public function getLockAdapter(string $vaultTitle): LockAdapterInterface
    {
        $vaultConfiguration = $this->configuration->getVaultConfigurationByTitle($vaultTitle);

        if ($vaultConfiguration === null)
        {
            throw new Exception(sprintf('Unknown vault title: "%s".', $vaultTitle));
        }

        $lockAdapter = $this->lockAdapterFactoryContainer->get($vaultConfiguration->getLockAdapter(), $vaultConfiguration);

        if ($lockAdapter === null)
        {
            throw new ConfigurationException(sprintf('Unknown lock adapter: "%s".', $vaultConfiguration->getLockAdapter()));
        }

        return $lockAdapter;
    }

    public function buildOperationList(): OperationList
    {
        $return = new OperationList();

        foreach ($this->getVaults() as $vault)
        {
            $return->append($vault->getOperationList());
        }

        return $return;
    }

    /**
     * @return Vault[]
     */
    public function getVaults(): array
    {
        return array_map(function(VaultConfiguration $vaultConfiguration) {

            return $this->getVault($vaultConfiguration->getTitle());

        }, $this->configuration->getVaultConfigurations());
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
            $vault->setIdentity($this->configuration->getIdentity());

            $this->vaults[$vaultTitle] = $vault;
        }

        return $this->vaults[$vaultTitle];
    }

    public function synchronize(array $vaultTitles = [], bool $preferLocal = false, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $lastRevision = 0;

        // fallback to all vaults
        $vaultTitles = $vaultTitles ?: array_map(function(Vault $vault) { return $vault->getTitle(); }, $this->getVaults());

        // acquire all locks and retrieve highest existing revision
        foreach ($this->getVaults() as $vault)
        {
            // lock is only required for vaults that we want to synchronize with
            if (in_array($vault->getTitle(), $vaultTitles))
            {
                $this->waitForLock($vault, Vault::LOCK_SYNC);
            }

            // highest revision should be build across all vaults
            $synchronizationList = $vault->loadSynchronizationList();
            if ($synchronizationList->getLastSynchronization())
            {
                $lastRevision = max($lastRevision, $synchronizationList->getLastSynchronization()->getRevision());
            }
        }

        // new revision is one plus the highest existing revision across all vaults
        $newRevision = $lastRevision + 1;

        // actual synchronization
        $return = new OperationResultList();
        foreach ($vaultTitles as $vaultTitle)
        {
            $return->append($this->getVault($vaultTitle)->synchronize($newRevision, $preferLocal, $progressListener));
        }

        // release lock at the last moment to further reduce change of deadlocks
        foreach ($vaultTitles as $vaultTitle)
        {
            $this->getVault($vaultTitle)->getLockAdapter()->releaseLock(Vault::LOCK_SYNC);
        }

        return $return;
    }

    /**
     * @return Synchronization[][]
     */
    public function buildSynchronizationHistory(): array
    {
        $return = [];

        foreach ($this->getVaults() as $vault)
        {
            $list = $vault->loadSynchronizationList();

            foreach ($list as $synchronization)
            {
                /** @var Synchronization $synchronization */

                $return[$synchronization->getRevision()][$vault->getTitle()] = $synchronization;
            }
        }

        ksort($return);

        return $return;
    }

    public function restore(int $toRevision = null, string $fromVault = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vault = $fromVault ? $this->getVault($fromVault) : $this->getAnyVault();

        $this->waitForLock($vault, Vault::LOCK_SYNC);

        $operationResultList = $vault->restore($toRevision, $progressListener);

        $vault->getLockAdapter()->releaseLock(Vault::LOCK_SYNC);

        return $operationResultList;
    }

    public function dump(string $targetPath, int $revision = null, string $fromVault = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vault = $fromVault ? $this->getVault($fromVault) : $this->getAnyVault();

        $this->waitForLock($vault, Vault::LOCK_SYNC);

        $operationResultList = $vault->dump($targetPath, $revision, $progressListener);

        $vault->getLockAdapter()->releaseLock(Vault::LOCK_SYNC);

        return $operationResultList;
    }

    protected function getAnyVault(): Vault
    {
        $vaults = array_values($this->getVaults());

        if (empty($vaults))
        {
            throw new ConfigurationException('No vaults defined.');
        }

        return $vaults[0];
    }

    protected function waitForLock(Vault $vault, string $name)
    {
        while (!$vault->getLockAdapter()->acquireLock($name))
        {
            sleep(5);
        }
    }
}
