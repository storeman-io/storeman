<?php

namespace Archivr\IndexMerger;

use Archivr\AbstractFactory;

class IndexMergerFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return IndexMergerInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        $return = [];

        $return['standard'] = function()
        {
            return new StandardIndexMerger();
        };

        return $return;
    }
}
