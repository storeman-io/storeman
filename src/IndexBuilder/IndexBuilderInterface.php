<?php

namespace Storeman\IndexBuilder;

use Storeman\Index;

interface IndexBuilderInterface
{
    public function buildIndexFromPath(string $path, array $excludedPathsRegexp = []): Index;
}
