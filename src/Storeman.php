<?php

namespace Storeman;

use Storeman\Exception\Exception;
use Storeman\SynchronizationProgressListener\SynchronizationProgressListenerInterface;

/**
 * This class coordinates executions with multiple vaults involved.
 */
class Storeman
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container();
        $this->container->injectStoreman($this);
    }

    public function getConfiguration(): Configuration
    {
        return $this->container->get('configuration');
    }

    /**
     * Returns the DI container of this storeman instance.
     * Some service names resolve to different implementations depending on the current vault which can be set as a context.
     * E.g. "storageAdapter" resolves to the storage adapter implementation configured for the current vault.
     *
     * @param Vault $vault
     * @return Container
     */
    public function getContainer(Vault $vault = null): Container
    {
        return $this->container->selectVault($vault);
    }

    /**
     * Returns a specific vault by title.
     *
     * @param string $vaultTitle
     * @return Vault
     */
    public function getVault(string $vaultTitle): Vault
    {
        $vaults = $this->container->getVaults();

        if (!$vaults->has($vaultTitle))
        {
            $vaultConfiguration = $this->getConfiguration()->getVault($vaultTitle);

            $vaults->register(new Vault($this, $vaultConfiguration));
        }

        return $vaults->get($vaultTitle);
    }

    /**
     * Returns all configured vaults.
     *
     * @return Vault[]
     */
    public function getVaults(): array
    {
        return array_values(array_map(function(VaultConfiguration $vaultConfiguration) {

            return $this->getVault($vaultConfiguration->getTitle());

        }, $this->getConfiguration()->getVaults()));
    }

    /**
     * Returns a subset of the configured vaults identified by the given set of titles.
     *
     * @param array $titles
     * @return Vault[]
     */
    public function getVaultsByTitle(array $titles): array
    {
        return array_filter($this->getVaults(), function(Vault $vault) use ($titles) {

            return in_array($vault->getVaultConfiguration()->getTitle(), $titles);
        });
    }

    /**
     * Returns all those vaults that have a given revision.
     *
     * @param int $revision
     * @return Vault[]
     */
    public function getVaultsHavingRevision(int $revision): array
    {
        return array_filter($this->getVaults(), function(Vault $vault) use ($revision) {

            return $vault->getVaultLayout()->getSynchronizations()->getSynchronization($revision) !== null;
        });
    }

    public function synchronize(array $vaultTitles = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vaults = ($vaultTitles === null) ? $this->getVaults() : $this->getVaultsByTitle($vaultTitles);

        $this->acquireLocks($vaults, Vault::LOCK_SYNC);

        $newRevision = ($this->getLastRevision() ?: 0) + 1;

        $return = new OperationResultList();
        foreach ($vaults as $vault)
        {
            $return->append($vault->synchronize($newRevision, $progressListener));
        }

        $this->releaseLocks($vaults, Vault::LOCK_SYNC);

        return $return;
    }

    public function restore(int $toRevision = null, string $fromVault = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vault = $this->getVaultForDownload($toRevision, $fromVault);

        if ($vault === null)
        {
            return new OperationResultList();
        }

        $operationResultList = $vault->restore($toRevision, $progressListener);

        return $operationResultList;
    }

    public function dump(string $targetPath, int $revision = null, string $fromVault = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vault = $this->getVaultForDownload($revision, $fromVault);

        if ($vault === null)
        {
            return new OperationResultList();
        }

        $operationResultList = $vault->dump($targetPath, $revision, $progressListener);

        return $operationResultList;
    }

    /**
     * Returns the highest revision number across all vaults.
     *
     * @return int
     */
    public function getLastRevision(): ?int
    {
        $max = 0;

        foreach ($this->getVaults() as $vault)
        {
            if ($lastSynchronization = $vault->getVaultLayout()->getLastSynchronization())
            {
                $max = max($max, $lastSynchronization->getRevision());
            }
        }

        return $max ?: null;
    }

    /**
     * Builds and returns a history of all synchronizations on record for this archive.
     *
     * @return Synchronization[][]
     */
    public function buildSynchronizationHistory(): array
    {
        $return = [];

        foreach ($this->getVaults() as $vault)
        {
            $vaultConfig = $vault->getVaultConfiguration();
            $list = $vault->loadSynchronizationList();

            foreach ($list as $synchronization)
            {
                /** @var Synchronization $synchronization */

                $return[$synchronization->getRevision()][$vaultConfig->getTitle()] = $synchronization;
            }
        }

        ksort($return);

        return $return;
    }

    /**
     * @param Vault[] $vaults
     * @param string $lockName
     */
    protected function acquireLocks(array $vaults, string $lockName)
    {
        foreach ($vaults as $vault)
        {
            if (!$vault->getLockAdapter()->acquireLock($lockName))
            {
                throw new Exception("Failed to acquire lock for vault {$vault->getVaultConfiguration()->getTitle()}");
            }
        }
    }

    /**
     * @param Vault[] $vaults
     * @param string $lockName
     */
    protected function releaseLocks(array $vaults, string $lockName)
    {
        foreach ($vaults as $vault)
        {
            if (!$vault->getLockAdapter()->releaseLock($lockName))
            {
                throw new Exception("Failed to release lock for vault {$vault->getVaultConfiguration()->getTitle()}");
            }
        }
    }

    protected function getVaultForDownload(?int $revision, ?string $vaultTitle): ?Vault
    {
        $revision = $revision ?: $this->getLastRevision();
        if ($revision === null)
        {
            return null;
        }

        if ($vaultTitle)
        {
            $vault = $this->getVault($vaultTitle);
        }
        else
        {
            $vaults = $this->getVaultsHavingRevision($revision);
            $vault = reset($vaults) ?: null;
        }

        if ($vault === null)
        {
            throw new Exception("Cannot find requested revision #{$revision} in any configured vault.");
        }

        return $vault;
    }
}
