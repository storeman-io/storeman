<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractServiceFactoryContainer;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;

class LockAdapterFactoryContainer extends AbstractServiceFactoryContainer
{
    public function get(string $name, VaultConfiguration $vaultConfiguration)
    {
        if (!isset($this->map[$name]))
        {
            return null;
        }

        $lockAdapter = ($this->map[$name])($vaultConfiguration);

        if (!($lockAdapter instanceof LockAdapterInterface))
        {
            throw new Exception(sprintf('Factory closure for lock adapter "%s" does not return an instance of %s!', $name, LockAdapterInterface::class));
        }

        return $lockAdapter;
    }
}
