<?php

namespace Archivr;

abstract class PathUtils
{
    /**
     * If a path relative to the user home is given this function expands the path to an absolute path.
     *
     * @param string $path
     * @return string
     */
    public static function expandTilde(string $path): string
    {
        if (substr($path, 0, 1) === '~')
        {
            $path = posix_getpwuid(posix_getuid())['dir'] . substr($path, 1);
        }

        return $path;
    }

    /**
     * Returns the absolute path without the need for the path actually existing.
     *
     * @see https://php.net/manual/en/function.realpath.php#84012
     * @param string $path
     * @return string
     */
    public static function getAbsolutePath(string $path): string
    {
        if (($expanded = static::expandTilde($path)) !== $path)
        {
            return $expanded;
        }

        $pathParts = array_filter(explode('/', $path), 'strlen');
        $absolutePathParts = [];

        foreach ($pathParts as $part)
        {
            if ('.' == $part)
            {
                continue;
            }
            if ('..' == $part)
            {
                array_pop($absolutePathParts);
            }
            else
            {
                $absolutePathParts[] = $part;
            }
        }

        $return = implode('/', $absolutePathParts);

        if (substr($path, 0, 1) === '/')
        {
            return '/' . $return;
        }
        else
        {
            return getcwd() . '/' . $return;
        }
    }
}
