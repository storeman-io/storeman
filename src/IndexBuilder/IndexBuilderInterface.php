<?php

namespace Storeman\IndexBuilder;

use Storeman\Index\Index;

/**
 * Builds an index from a local directory.
 */
interface IndexBuilderInterface
{
    public function buildIndexFromPath(string $path, array $excludedPathsRegexp = []): Index;
}
