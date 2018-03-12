<?php

namespace Archivr;

use Archivr\Exception\ConfigurationException;
use Archivr\SynchronizationProgressListener\SynchronizationProgressListenerInterface;

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

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Returns a specific vault by title.
     *
     * @param string $vaultTitle
     * @return Vault
     */
    public function getVault(string $vaultTitle): Vault
    {
        if (!isset($this->vaults[$vaultTitle]))
        {
            $vaultConfiguration = $this->getConfiguration()->getVaultByTitle($vaultTitle);

            $this->vaults[$vaultTitle] = new Vault($this, $vaultConfiguration);
        }

        return $this->vaults[$vaultTitle];
    }

    /**
     * Returns all vaults within this archive.
     *
     * @return Vault[]
     */
    public function getVaults(): array
    {
        return array_values(array_map(function(VaultConfiguration $vaultConfiguration) {

            return $this->getVault($vaultConfiguration->getTitle());

        }, $this->configuration->getVaults()));
    }

    /**
     * Builds and returns an OperationList instance containing all operations required to a full synchronization.
     *
     * @return OperationList
     */
    public function buildOperationList(): OperationList
    {
        $return = new OperationList();

        foreach ($this->getVaults() as $vault)
        {
            $return->append($vault->getOperationList());
        }

        return $return;
    }

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
        $vault = $fromVault ? $this->getVault($fromVault) : $this->getAnyVault();
        $lockAdapter = $vault->getLockAdapter();

        $lockAdapter->acquireLock(Vault::LOCK_SYNC);

        $operationResultList = $vault->restore($toRevision, $progressListener);

        $lockAdapter->releaseLock(Vault::LOCK_SYNC);

        return $operationResultList;
    }

    public function dump(string $targetPath, int $revision = null, string $fromVault = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $vault = $fromVault ? $this->getVault($fromVault) : $this->getAnyVault();
        $lockAdapter = $vault->getLockAdapter();

        $lockAdapter->acquireLock(Vault::LOCK_SYNC);

        $operationResultList = $vault->dump($targetPath, $revision, $progressListener);

        $lockAdapter->releaseLock(Vault::LOCK_SYNC);

        return $operationResultList;
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

    protected function getAnyVault(): Vault
    {
        $vaults = array_values($this->getVaults());

        if (empty($vaults))
        {
            throw new ConfigurationException('No vaults defined.');
        }

        return $vaults[0];
    }
}
