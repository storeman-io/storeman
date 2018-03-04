<?php

namespace Archivr\StorageDriver;

use Archivr\AbstractFactory;
use Archivr\Exception\ConfigurationException;
use Archivr\Exception\Exception;
use Archivr\TildeExpansionTrait;
use Archivr\VaultConfiguration;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class StorageDriverFactory extends AbstractFactory
{
    use TildeExpansionTrait;

    public function __construct()
    {
        $this->factoryMap['dummy'] = function ()
        {
            return new DummyStorageDriver();
        };

        $this->factoryMap['path'] = function(VaultConfiguration $vaultConfiguration)
        {
            $path = $vaultConfiguration->getSetting('path');
            $path = $this->expandTildePath($path);

            if (!is_dir($path) || !is_writable($path))
            {
                throw new ConfigurationException(sprintf('Path "%s" does not exist or is not writable.', $path));
            }

            $adapter = new Local($path);
            $filesystem = new Filesystem($adapter);

            return new FlysystemStorageDriver($filesystem);
        };
    }

    public function create(string $name, ...$params)
    {
        $driver = parent::create($name, ...$params);

        if (!($driver instanceof StorageDriverInterface))
        {
            throw new Exception(sprintf('Factory closure for driver "%s" does not return an instance of %s!', $name, StorageDriverInterface::class));
        }

        return $driver;
    }
}
