<?php

namespace Storeman\IndexBuilder;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Storeman\Exception;
use Storeman\Hash\HashContainer;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StandardIndexBuilder implements IndexBuilderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function buildIndex(string $path, array $excludedPathsRegexp = []): Index
    {
        $this->logger->info(sprintf("Building index using %s for path '%s' (excluded: %s)...", static::class, $path, implode(',', $excludedPathsRegexp) ?: '-'));

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

            if ($indexObject = $this->buildIndexObject($path, $fileInfo->getRelativePathname()))
            {
                $index->addObject($indexObject);
            }
        }

        foreach ($finder->files() as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            if ($indexObject = $this->buildIndexObject($path, $fileInfo->getRelativePathname()))
            {
                $index->addObject($indexObject);
            }
        }

        return $index;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIndexObject(string $basePath, string $relativePath): ?IndexObject
    {
        $absolutePath = rtrim($basePath, '/') . '/' . $relativePath;

        clearstatcache(null, $absolutePath);

        if (!($stat = @lstat($absolutePath)))
        {
            throw new Exception("lstat() failed for {$absolutePath}");
        }

        $size = $linkTarget = $hashContainer = null;

        switch ($stat['mode'] & 0xF000)
        {
            case 0x4000:

                $type = IndexObject::TYPE_DIR;

                break;

            case 0x8000:

                $type = IndexObject::TYPE_FILE;
                $size = $stat['size'];
                $hashContainer = new HashContainer();

                break;

            case 0xA000:

                $type = IndexObject::TYPE_LINK;
                $linkTarget = readlink($absolutePath);

                if ($linkTarget === false)
                {
                    $this->logger->notice("Found broken link: {$absolutePath}");

                    // silently ignore broken links
                    return null;
                }

                break;

            default:

                // sockets, pipes, etc.
                return null;
        }

        return new IndexObject($relativePath, $type, $stat['mtime'], $stat['ctime'], $stat['mode'] & 0777, $size, $stat['ino'], $linkTarget, null, $hashContainer);
    }
}
