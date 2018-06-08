<?php

namespace Storeman\OperationListBuilder;

use Storeman\AbstractFactory;

final class OperationListBuilderFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return OperationListBuilderInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        return [
            'standard' => StandardOperationListBuilder::class,
        ];
    }
}
