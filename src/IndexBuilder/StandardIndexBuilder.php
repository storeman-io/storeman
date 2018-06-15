<?php

namespace Storeman\IndexBuilder;

use Storeman\Index;
use Storeman\IndexObject;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StandardIndexBuilder implements IndexBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildIndexFromPath(string $path, array $excludedPathsRegexp = []): Index
    {
        if (!is_dir($path) || !is_readable($path))
        {
            throw new \InvalidArgumentException("Given path {$path} does not exist or is not readable.");
        }

        $finder = new Finder();
        $finder->in($path);
        $finder->ignoreDotFiles(false);

        foreach ($excludedPathsRegexp as $excludedPathRegexp)
        {
            $finder->notPath($excludedPathRegexp);
        }

        $index = new Index();

        foreach ($finder->directories() as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $index->addObject(IndexObject::fromPath($path, $fileInfo->getRelativePathname()));
        }

        foreach ($finder->files() as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $index->addObject(IndexObject::fromPath($path, $fileInfo->getRelativePathname()));
        }

        // todo: add symlinks

        return $index;
    }
}
