<?php

namespace Storeman\IndexMerger;

use Storeman\AbstractFactory;

final class IndexMergerFactory extends AbstractFactory
{
    protected static function requiresInstanceOf(): string
    {
        return IndexMergerInterface::class;
    }

    protected static function getFactoryMap(): array
    {
        return [
            'standard' => StandardIndexMerger::class,
        ];
    }
}
