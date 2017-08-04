<?php

namespace Archivr\ConnectionAdapter;

use Archivr\AbstractServiceFactoryContainer;
use Archivr\ConnectionConfiguration;

class ConnectionAdapterFactoryContainer extends AbstractServiceFactoryContainer
{
    public function get(string $name, ConnectionConfiguration $connectionConfiguration)
    {
        if (!isset($this->map[$name]))
        {
            return null;
        }

        $connection = ($this->map[$name])($connectionConfiguration);

        if (!($connection instanceof ConnectionAdapterInterface))
        {
            throw new \LogicException(sprintf('Factory closure for connection adapter "%s" does not return an instance of %s!', $name, ConnectionAdapterInterface::class));
        }

        return $connection;
    }
}
