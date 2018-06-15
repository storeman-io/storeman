<?php

namespace Storeman\Test\VaultLayout;

use Storeman\Index;
use Storeman\Test\IndexTest;
use Storeman\VaultLayout\LazyLoadedIndex;

class LazyLoadedIndexTest extends IndexTest
{
    protected function getNewIndex(): Index
    {
        return new LazyLoadedIndex(function() {

            return new Index();
        });
    }
}
