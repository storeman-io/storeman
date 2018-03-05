<?php

namespace Archivr\OperationListBuilder;

use Archivr\AbstractFactory;

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
