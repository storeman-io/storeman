<?php

namespace Storeman\Test;

use Storeman\Cli\ConsoleStyle;
use Storeman\Config\Configuration;
use Storeman\Hash\HashProvider;
use Storeman\Index\IndexObject;
use Storeman\OperationListItem;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @method createConfiguredMock(string $className, array $config)
 */
trait ConfiguredMockProviderTrait
{
    private function getOperationListItemMock(array $config = []): OperationListItem
    {
        return $this->createConfiguredMock(OperationListItem::class, $config);
    }

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

    protected function getConsoleStyleMock(array $config = []): ConsoleStyle
    {
        return $this->createConfiguredMock(ConsoleStyle::class, $config);
    }

    protected function getInputMock(array $config = []): InputInterface
    {
        return $this->createConfiguredMock(InputInterface::class, $config);
    }
}
