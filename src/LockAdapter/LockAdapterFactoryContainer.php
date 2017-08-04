<?php

namespace Archivr\LockAdapter;

use Archivr\AbstractServiceFactoryContainer;
use Archivr\ConnectionConfiguration;

class LockAdapterFactoryContainer extends AbstractServiceFactoryContainer
{
    public function get(string $name, ConnectionConfiguration $connectionConfiguration)
    {
        if (!isset($this->map[$name]))
        {
            return null;
        }

        $lockAdapter = ($this->map[$name])($connectionConfiguration);

        if (!($lockAdapter instanceof LockAdapterInterface))
        {
            throw new \LogicException(sprintf('Factory closure for lock adapter "%s" does not return an instance of %s!', $name, LockAdapterInterface::class));
        }

        return $lockAdapter;
    }
}
