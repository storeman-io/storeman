<?php

namespace Storeman;

use Storeman\Config\Configuration;
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
     * Returns the configuration.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->container->get('configuration');
    }

    /**
     * Returns a container of the configured vaults.
     *
     * @return VaultContainer
     */
    public function getVaultContainer(): VaultContainer
    {
        return $this->container->getVaultContainer();
    }

    public function synchronize(array $vaultTitles = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        /** @var Vault[] $vaults */
        $vaults = ($vaultTitles === null) ? $this->getVaultContainer() : $this->getVaultContainer()->getVaultsByTitles($vaultTitles);

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

        foreach ($this->getVaultContainer() as $vault)
        {
            /** @var Vault $vault */

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

        foreach ($this->getVaultContainer() as $vault)
        {
            /** @var Vault $vault */

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
    protected function acquireLocks(\Traversable $vaults, string $lockName)
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
    protected function releaseLocks(\Traversable $vaults, string $lockName)
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

        $vaultContainer = $this->getVaultContainer();

        if ($vaultTitle)
        {
            $vault = $vaultContainer->getVaultByTitle($vaultTitle);
        }
        else
        {
            $vaults = $vaultContainer->getVaultsHavingRevision($revision);
            $vault = $vaultContainer->getPrioritizedVault($vaults);
        }

        if ($vault === null)
        {
            throw new Exception("Cannot find requested revision #{$revision} in any configured vault.");
        }

        return $vault;
    }
}
