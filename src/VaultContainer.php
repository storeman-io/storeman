<?php

namespace Storeman;

use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Storeman\Exception\ConfigurationException;

final class VaultContainer implements ContainerInterface, \Countable, \IteratorAggregate
{
    /**
     * @var Vault[]
     */
    protected $vaults = [];

    public function __construct(Configuration $configuration, Storeman $storeman)
    {
        foreach ($configuration->getVaults() as $vaultConfiguration)
        {
            if (array_key_exists($vaultConfiguration->getTitle(), $this->vaults))
            {
                throw new ConfigurationException("Duplicate vault title: {$vaultConfiguration->getTitle()}");
            }

            $this->vaults[$vaultConfiguration->getTitle()] = new Vault($storeman, $vaultConfiguration);
        }
    }

    /**
     * Returns a vault by its title.
     *
     * @param string $title
     * @return Vault
     */
    public function getVaultByTitle(string $title): ?Vault
    {
        return array_key_exists($title, $this->vaults) ? $this->vaults[$title] : null;
    }

    /**
     * Returns a subset of the configured vaults identified by the given set of titles.
     *
     * @param array $titles
     * @return Vault[]
     */
    public function getVaultsByTitles(array $titles): array
    {
        return array_filter($this->vaults, function(Vault $vault) use ($titles) {

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
        return array_filter($this->vaults, function(Vault $vault) use ($revision) {

            return $vault->getVaultLayout()->getSynchronizations()->getSynchronization($revision) !== null;
        });
    }

    /**
     * Returns the vault with the highest priority.
     *
     * @param Vault[] $vaults Vaults to consider. Defaults to all configured vaults.
     * @return Vault
     */
    public function getPrioritizedVault(array $vaults = null): ?Vault
    {
        /** @var Vault[] $vaults */
        $vaults = ($vaults === null) ? $this : $vaults;

        /** @var Vault $return */
        $return = null;

        foreach ($vaults as $vault)
        {
            if ($return === null || $return->getVaultConfiguration()->getPriority() < $vault->getVaultConfiguration()->getPriority())
            {
                $return = $vault;
            }
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->getVaultByTitle($id) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($vault = $this->getVaultByTitle($id))
        {
            return $vault;
        }

        throw new NotFoundException();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->vaults);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->vaults));
    }
}
