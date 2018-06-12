<?php

namespace Storeman;

use League\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

final class VaultContainer implements ContainerInterface
{
    /**
     * @var Vault[]
     */
    protected $vaults = [];

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->getVault($id) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($vault = $this->getVault($id))
        {
            return $vault;
        }

        throw new NotFoundException();
    }

    public function register(Vault $vault): void
    {
        $title = $vault->getVaultConfiguration()->getTitle();

        if ($this->has($title))
        {
            throw new \RuntimeException("There is already a vault named {$title} registered.");
        }

        $this->vaults[$title] = $vault;
    }

    protected function getVault(string $title): ?Vault
    {
        foreach ($this->vaults as $vault)
        {
            if ($vault->getVaultConfiguration()->getTitle() === $title)
            {
                return $vault;
            }
        }

        return null;
    }
}
