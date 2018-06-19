<?php

namespace Storeman\Test\IndexBuilder;

use Storeman\IndexBuilder\IndexBuilderInterface;
use Storeman\IndexBuilder\StandardIndexBuilder;

class StandardIndexBuilderTest extends AbstractIndexBuilderTest
{
    protected function getIndexBuilder(): IndexBuilderInterface
    {
        return new StandardIndexBuilder();
    }
}
