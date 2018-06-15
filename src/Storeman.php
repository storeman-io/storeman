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

    /**
     * @todo: subdivide
     */
    public function synchronize(array $vaultTitles = [], SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $lastRevision = 0;

        // fallback to all vaults
        $vaultTitles = $vaultTitles ?: array_map(function(Vault $vault) { return $vault->getVaultConfiguration()->getTitle(); }, $this->getVaults());

        // acquire all locks and retrieve highest existing revision
        foreach ($this->getVaults() as $vault)
        {
            // lock is only required for vaults that we want to synchronize with
            if (in_array($vault->getVaultConfiguration()->getTitle(), $vaultTitles))
            {
                $vault->getLockAdapter()->acquireLock(Vault::LOCK_SYNC);
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
            $return->append($this->getVault($vaultTitle)->synchronize($newRevision, $progressListener));
        }

        // release lock at the last moment to further reduce change of deadlocks
        foreach ($vaultTitles as $vaultTitle)
        {
            $this->getVault($vaultTitle)->getLockAdapter()->releaseLock(Vault::LOCK_SYNC);
        }

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

    protected function getVaultForDownload(?int $revision, ?string $vaultTitle): Vault
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
