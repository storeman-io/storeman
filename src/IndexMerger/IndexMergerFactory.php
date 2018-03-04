<?php

namespace Archivr\IndexMerger;

use Archivr\AbstractFactory;

class IndexMergerFactory extends AbstractFactory
{
    protected static $requiresInstanceOf = IndexMergerInterface::class;

    public function __construct()
    {
        $this->factoryMap['standard'] = function()
        {
            return new StandardIndexMerger();
        };
    }
}
