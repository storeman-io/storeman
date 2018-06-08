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

    public function register(Vault $vault): void
    {
        $title = $vault->getVaultConfiguration()->getTitle();

        if ($this->has($title))
        {
            throw new \RuntimeException("There is already a vault named {$title} registered.");
        }

        $this->vaults[$title] = $vault;
    }
}
