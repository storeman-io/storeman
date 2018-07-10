<?php

namespace Storeman;

use Psr\Log\LoggerInterface;
use Storeman\Config\Configuration;
use Storeman\Index\Index;
use Storeman\IndexBuilder\IndexBuilderInterface;
use Storeman\SynchronizationProgressListener\SynchronizationProgressListenerInterface;

/**
 * This class coordinates executions with multiple vaults involved.
 */
class Storeman
{
    public const CONFIG_FILE_NAME = 'storeman.json';
    public const METADATA_DIRECTORY_NAME = '.storeman';

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

    /**
     * Returns configured index builder.
     *
     * @return IndexBuilderInterface
     */
    public function getIndexBuilder(): IndexBuilderInterface
    {
        return $this->container->get('indexBuilder');
    }

    /**
     * @return FileReader
     */
    public function getFileReader(): FileReader
    {
        return $this->container->get('fileReader');
    }

    /**
     * Builds and returns an index representing the current local state.
     *
     * @param string $path
     * @return Index
     */
    public function getLocalIndex(string $path = null): Index
    {
        return $this->getIndexBuilder()->buildIndex(
            $path ?: $this->getConfiguration()->getPath(),
            $this->getLocalIndexExclusionPatterns()
        );
    }

    public function synchronize(array $vaultTitles = null, SynchronizationProgressListenerInterface $progressListener = null): OperationResultList
    {
        $this->getLogger()->notice(sprintf("Synchronizing to these vaults: %s", $vaultTitles ? implode(', ', $vaultTitles) : '-all-'));

        /** @var Vault[] $vaults */
        $vaults = ($vaultTitles === null) ? $this->getVaultContainer() : $this->getVaultContainer()->getVaultsByTitles($vaultTitles);

        // vault list order is ensured to be consistent across instances by the VaultContainer which is mandatory for deadlock prevention
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
        $this->getLogger()->notice(sprintf("Restoring from %s...", $toRevision ? "r{$toRevision}": 'latest revision'));

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
        $this->getLogger()->notice(sprintf("Dumping from %s to {$targetPath}...", $revision ? "r{$revision}": 'latest revision'));

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
        $this->getLogger()->debug("Determining max revision...");

        $max = 0;

        foreach ($this->getVaultContainer() as $vault)
        {
            /** @var Vault $vault */

            if ($lastSynchronization = $vault->getVaultLayout()->getLastSynchronization())
            {
                $this->getLogger()->debug("Vault {$vault->getIdentifier()} is at r{$lastSynchronization->getRevision()}");

                $max = max($max, $lastSynchronization->getRevision());
            }
            else
            {
                $this->getLogger()->debug("Vault {$vault->getIdentifier()} has no synchronizations yet");
            }
        }

        $this->getLogger()->info("Found max revision to be " . ($max ?: '-'));

        return $max ?: null;
    }

    public function getMetadataDirectoryPath(): string
    {
        return $this->initMetadataDirectory();
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
                throw new Exception("Failed to acquire lock for vault {$vault->getIdentifier()}");
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
                throw new Exception("Failed to release lock for vault {$vault->getIdentifier()}");
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

    protected function initMetadataDirectory(): string
    {
        $path = $this->getConfiguration()->getPath() . static::METADATA_DIRECTORY_NAME;

        if (!is_dir($path))
        {
            if (!mkdir($path))
            {
                throw new Exception("mkdir() failed for {$path}");
            }
        }

        return "{$path}/";
    }

    /**
     * @return string[]
     */
    protected function getLocalIndexExclusionPatterns(): array
    {
        return array_merge($this->getConfiguration()->getExclude(), [
            sprintf('/(^|\/)%s$/', preg_quote(static::CONFIG_FILE_NAME)),
            sprintf('/(^|\/)%s($|\/)/', preg_quote(static::METADATA_DIRECTORY_NAME)),
        ]);
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->container->getLogger();
    }
}
