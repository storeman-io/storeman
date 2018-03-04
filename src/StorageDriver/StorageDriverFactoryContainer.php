<?php

namespace Archivr\StorageDriver;

use Archivr\AbstractServiceFactoryContainer;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;

class StorageDriverFactoryContainer extends AbstractServiceFactoryContainer
{
    public function get(string $name, VaultConfiguration $vaultConfiguration)
    {
        if (!isset($this->map[$name]))
        {
            return null;
        }

        $driver = ($this->map[$name])($vaultConfiguration);

        if (!($driver instanceof StorageDriverInterface))
        {
            throw new Exception(sprintf('Factory closure for driver "%s" does not return an instance of %s!', $name, StorageDriverInterface::class));
        }

        return $driver;
    }
}
