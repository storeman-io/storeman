<?php

namespace Storeman\IndexBuilder;

use Storeman\Index\Index;
use Storeman\Index\IndexObject;

/**
 * Builds an index from a local directory.
 */
interface IndexBuilderInterface
{
    /**
     * Builds a complete index of the given path by iterating over its content and constructing individual IndexObject instances.
     *
     * @param string $path
     * @param array $excludedPathsRegexp
     * @return Index
     */
    public function buildIndex(string $path, array $excludedPathsRegexp = []): Index;

    /**
     * Builds an IndexObject of a single object under the given path.
     *
     * @param string $basePath
     * @param string $relativePath
     * @return IndexObject
     */
    public function buildIndexObject(string $basePath, string $relativePath): ?IndexObject;
}
