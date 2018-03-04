<?php

namespace Archivr\ConnectionAdapter;

use Archivr\AbstractServiceFactoryContainer;
use Archivr\VaultConfiguration;
use Archivr\Exception\Exception;

class ConnectionAdapterFactoryContainer extends AbstractServiceFactoryContainer
{
    public function get(string $name, VaultConfiguration $vaultConfiguration)
    {
        if (!isset($this->map[$name]))
        {
            return null;
        }

        $connection = ($this->map[$name])($vaultConfiguration);

        if (!($connection instanceof ConnectionAdapterInterface))
        {
            throw new Exception(sprintf('Factory closure for connection adapter "%s" does not return an instance of %s!', $name, ConnectionAdapterInterface::class));
        }

        return $connection;
    }
}
