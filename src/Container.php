<?php

namespace Storeman;

use League\Container\Definition\DefinitionInterface;
use Psr\Container\ContainerInterface;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PanickingConflictHandler;
use Storeman\ConflictHandler\PreferLocalConflictHandler;
use Storeman\ConflictHandler\PreferRemoteConflictHandler;
use Storeman\IndexBuilder\IndexBuilderInterface;
use Storeman\IndexBuilder\StandardIndexBuilder;
use Storeman\IndexMerger\IndexMergerInterface;
use Storeman\IndexMerger\StandardIndexMerger;
use Storeman\LockAdapter\DummyLockAdapter;
use Storeman\LockAdapter\LockAdapterInterface;
use Storeman\LockAdapter\StorageBasedLockAdapter;
use Storeman\OperationListBuilder\OperationListBuilderInterface;
use Storeman\OperationListBuilder\StandardOperationListBuilder;
use Storeman\StorageAdapter\LocalStorageAdapter;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Validation\Constraints\ExistingServiceValidator;
use Storeman\VaultLayout\Amberjack\AmberjackVaultLayout;
use Storeman\VaultLayout\VaultLayoutInterface;

/**
 * Dependency injection container for modularized parts.
 * It is basically a facade for a PSR container implementation with some additional accessors for type safety.
 */
final class Container implements ContainerInterface
{
    protected const PREFIX_CONFLICT_HANDLER = 'conflictHandler.';
    protected const PREFIX_INDEX_BUILDER = 'indexBuilder.';
    protected const PREFIX_INDEX_MERGER = 'indexMerger.';
    protected const PREFIX_LOCK_ADAPTER = 'lockAdapter.';
    protected const PREFIX_OPERATION_LIST_BUILDER = 'operationListBuilder.';
    protected const PREFIX_STORAGE_ADAPTER = 'storageAdapter.';
    protected const PREFIX_VAULT_LAYOUT = 'vaultLayout.';


    /**
     * @var InspectableContainer
     */
    protected $delegate;

    /**
     * Sets up the container with all bundled services.
     *
     * Note: Be very careful with shared services as the container is used shared by all vaults of a storeman instance.
     */
    public function __construct()
    {
        $this->delegate = new InspectableContainer();

        $this->delegate->add('vaults', VaultContainer::class, true)->withArguments(['configuration', 'storeman']);
        $this->delegate->add('vaultConfiguration', function(Vault $vault) { return $vault->getVaultConfiguration(); })->withArgument('vault');

        $this->registerServiceFactory('indexBuilder');
        $this->addIndexBuilder('standard', StandardIndexBuilder::class, true);

        $this->registerVaultServiceFactory('conflictHandler');
        $this->addConflictHandler('panicking', PanickingConflictHandler::class, true);
        $this->addConflictHandler('preferLocal', PreferLocalConflictHandler::class, true);
        $this->addConflictHandler('preferRemote', PreferRemoteConflictHandler::class, true);

        $this->registerVaultServiceFactory('indexMerger');
        $this->addIndexMerger('standard', StandardIndexMerger::class, true);

        $this->registerVaultServiceFactory('lockAdapter');
        $this->addLockAdapter('dummy', DummyLockAdapter::class);
        $this->addLockAdapter('storage', StorageBasedLockAdapter::class)->withArgument('storageAdapter');

        $this->registerVaultServiceFactory('operationListBuilder');
        $this->addOperationListBuilder('standard', StandardOperationListBuilder::class, true);

        $this->registerVaultServiceFactory('storageAdapter', 'adapter');
        $this->addStorageAdapter('local', LocalStorageAdapter::class)->withArgument('vaultConfiguration');

        $this->registerVaultServiceFactory('vaultLayout');
        $this->addVaultLayout('amberjack', AmberjackVaultLayout::class)->withArguments(['storageAdapter', 'vaultConfiguration']);

        $this->delegate->add(ExistingServiceValidator::class)->withArgument($this);
    }

    public function injectConfiguration(Configuration $configuration): Container
    {
        if ($this->has('configuration'))
        {
            throw new \RuntimeException();
        }

        $this->delegate->add('configuration', $configuration, true);

        return $this;
    }

    public function injectStoreman(Storeman $storeman): Container
    {
        if ($this->has('storeman'))
        {
            throw new \RuntimeException();
        }

        $this->delegate->add('storeman', $storeman, true);

        return $this;
    }

    /**
     * Selects the given vault (or no vault) as the current one.
     *
     * @param Vault $vault
     * @return Container
     */
    public function selectVault(Vault $vault = null): Container
    {
        $this->delegate->add('vault', $vault, true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->delegate->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->delegate->has($id);
    }


    public function getStoreman(): Storeman
    {
        return $this->get('storeman');
    }

    public function getConfiguration(): Configuration
    {
        return $this->get('configuration');
    }

    public function getVaultContainer(): VaultContainer
    {
        return $this->get('vaults');
    }

    public function getSelectedVault(): ?Vault
    {
        return $this->get('vault');
    }


    public function getConflictHandler(string $name): ConflictHandlerInterface
    {
        return $this->delegate->get($this->getConflictHandlerServiceName($name));
    }

    public function addConflictHandler(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getConflictHandlerServiceName($name), $concrete, $shared);
    }

    public function getConflictHandlerNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_CONFLICT_HANDLER);
    }


    public function getIndexMerger(string $name): IndexMergerInterface
    {
        return $this->delegate->get($this->getIndexMergerServiceName($name));
    }

    public function addIndexMerger(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getIndexMergerServiceName($name), $concrete, $shared);
    }

    public function getIndexMergerNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_INDEX_MERGER);
    }


    public function getIndexBuilder(string $name): IndexBuilderInterface
    {
        return $this->delegate->get($this->getIndexBuilderServiceName($name));
    }

    public function addIndexBuilder(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getIndexBuilderServiceName($name), $concrete, $shared);
    }

    public function getIndexBuilderNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_INDEX_BUILDER);
    }


    public function getLockAdapter(string $name): LockAdapterInterface
    {
        return $this->delegate->get($this->getLockAdapterServiceName($name));
    }

    public function addLockAdapter(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getLockAdapterServiceName($name), $concrete, $shared);
    }

    public function getLockAdapterNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_LOCK_ADAPTER);
    }

    
    public function getOperationListBuilder(string $name): OperationListBuilderInterface
    {
        return $this->delegate->get($this->getOperationListBuilderServiceName($name));
    }

    public function addOperationListBuilder(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getOperationListBuilderServiceName($name), $concrete, $shared);
    }

    public function getOperationListBuilderNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_OPERATION_LIST_BUILDER);
    }


    public function getStorageAdapter(string $name): StorageAdapterInterface
    {
        return $this->delegate->get($this->getStorageAdapterServiceName($name));
    }

    public function addStorageAdapter(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getStorageAdapterServiceName($name), $concrete, $shared);
    }

    public function getStorageAdapterNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_STORAGE_ADAPTER);
    }


    public function getVaultLayout(string $name): VaultLayoutInterface
    {
        return $this->delegate->get($this->getVaultLayoutServiceName($name));
    }

    public function addVaultLayout(string $name, $concrete, bool $shared = false): DefinitionInterface
    {
        return $this->delegate->add($this->getVaultLayoutServiceName($name), $concrete, $shared);
    }

    public function getVaultLayoutNames(): array
    {
        return $this->getServiceNamesWithPrefix(static::PREFIX_VAULT_LAYOUT);
    }


    protected function getConflictHandlerServiceName(string $name): string
    {
        return static::PREFIX_CONFLICT_HANDLER . $name;
    }

    protected function getIndexBuilderServiceName(string $name): string
    {
        return static::PREFIX_INDEX_BUILDER . $name;
    }

    protected function getIndexMergerServiceName(string $name): string
    {
        return static::PREFIX_INDEX_MERGER . $name;
    }

    protected function getLockAdapterServiceName(string $name): string
    {
        return static::PREFIX_LOCK_ADAPTER . $name;
    }

    protected function getOperationListBuilderServiceName(string $name): string
    {
        return static::PREFIX_OPERATION_LIST_BUILDER . $name;
    }

    protected function getStorageAdapterServiceName(string $name): string
    {
        return static::PREFIX_STORAGE_ADAPTER . $name;
    }

    protected function getVaultLayoutServiceName(string $name): string
    {
        return static::PREFIX_VAULT_LAYOUT . $name;
    }

    /**
     * Registers a factory for a type of service.
     *
     * @param string $type
     * @param string $configurationKey
     */
    protected function registerServiceFactory(string $type, string $configurationKey = null): void
    {
        $this->delegate->add($type, function(Configuration $configuration) use ($configurationKey, $type) {

            $array = $configuration->getArrayCopy();
            $configurationKey = $configurationKey ?: $type;

            if (!array_key_exists($configurationKey, $array))
            {
                throw new \LogicException("Unknown config key: {$configuration}");
            }

            return $this->delegate->get("{$type}.{$array[$configurationKey]}");

        })->withArgument('configuration');
    }

    /**
     * Registers a factory for a type of vault-specific module.
     *
     * @param string $type
     * @param string $vaultConfigurationKey
     */
    protected function registerVaultServiceFactory(string $type, string $vaultConfigurationKey = null): void
    {
        $this->delegate->add($type, function(Vault $vault) use ($type, $vaultConfigurationKey) {

            $array = $vault->getVaultConfiguration()->getArrayCopy();
            $vaultConfigurationKey = $vaultConfigurationKey ?: $type;

            if (!array_key_exists($vaultConfigurationKey, $array))
            {
                throw new \LogicException("Unknown vault config key: {$vaultConfigurationKey}");
            }

            return $this->delegate->get("{$type}.{$array[$vaultConfigurationKey]}");

        })->withArgument('vault');
    }

    /**
     * Builds and returns list of added service names starting with a given prefix.
     *
     * @param string $prefix
     * @return string[]
     */
    protected function getServiceNamesWithPrefix(string $prefix): array
    {
        return array_map(

            // remove prefix
            function(string $alias) use ($prefix) {
                return substr($alias, strlen($prefix));
            },

            array_filter(

                // search in all registered services
                $this->delegate->getProvidedServiceNames(),

                // only service names with given prefix
                function(string $alias) use ($prefix) {
                    return substr($alias, 0, strlen($prefix)) === $prefix;
                }
            )
        );
    }
}
