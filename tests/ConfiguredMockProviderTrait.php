<?php

namespace Storeman\Test;

use Storeman\Config\Configuration;
use Storeman\Hash\HashProvider;
use Storeman\Index\IndexObject;

/**
 * @method createConfiguredMock(string $className, array $config)
 */
trait ConfiguredMockProviderTrait
{
    private function getConfigurationMock(array $config = []): Configuration
    {
        return $this->createConfiguredMock(Configuration::class, $config);
    }

    private function getIndexObjectMock(array $config = []): IndexObject
    {
        return $this->createConfiguredMock(IndexObject::class, $config);
    }

    protected function getHashProviderMock(array $config = []): HashProvider
    {
        return $this->createConfiguredMock(HashProvider::class, $config);
    }
}
