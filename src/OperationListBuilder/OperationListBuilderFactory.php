<?php

namespace Archivr\OperationListBuilder;

use Archivr\AbstractFactory;

class OperationListBuilderFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return OperationListBuilderInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['standard'] = function()
        {
            return new StandardOperationListBuilder();
        };

        return $return;
    }
}
