<?php

namespace Storeman\IndexBuilder;

use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StandardIndexBuilder implements IndexBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildIndexFromPath(string $path, array $excludedPathsRegexp = []): Index
    {
        if (!file_exists($path))
        {
            throw new \InvalidArgumentException("Given path '{$path}' does not exist.");
        }
        elseif (!is_dir($path))
        {
            throw new \InvalidArgumentException("Given path '{$path}' is not a directory.");
        }
        elseif (!is_readable($path))
        {
            throw new \InvalidArgumentException("Given directory '{$path}' is not readable.'");
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

        return $index;
    }
}
