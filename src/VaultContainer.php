<?php

namespace Storeman;

use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Storeman\Config\Configuration;
use Storeman\Config\ConfigurationException;

final class VaultContainer implements ContainerInterface, \Countable, \IteratorAggregate
{
    /**
     * @var Vault[]
     */
    protected $vaults = [];

    public function __construct(Configuration $configuration, Storeman $storeman, LoggerInterface $logger)
    {
        foreach ($configuration->getVaults() as $vaultConfiguration)
        {
            $vault = new Vault($storeman, $vaultConfiguration);
            $vault->setLogger($logger);

            if (array_key_exists($vault->getHash(), $this->vaults))
            {
                $existingVault = $this->vaults[$vault->getHash()];

                assert($existingVault instanceof Vault);

                throw new ConfigurationException("Duplicate vault hash for vaults '{$vault->getIdentifier()}' and '{$existingVault->getIdentifier()}'");
            }

            $this->vaults[$vault->getHash()] = $vault;
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
        foreach ($this as $vault)
        {
            /** @var Vault $vault */

            if ($vault->getVaultConfiguration()->getTitle() === $title)
            {
                return $vault;
            }
        }

        return null;
    }

    /**
     * Returns a subset of the configured vaults identified by the given set of titles.
     *
     * @param array $titles
     * @return Vault[]
     */
    public function getVaultsByTitles(array $titles): array
    {
        return array_filter(array_map(function(string $title) {

            return $this->getVaultByTitle($title);

        }, $titles));
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
        return array_key_exists($id, $this->vaults);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->vaults))
        {
            return $this->vaults[$id];
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
