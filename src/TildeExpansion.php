<?php

namespace Archivr;

class TildeExpansion
{
    /**
     * If a path relative to the user home is given this function expands the path to an absolute path.
     *
     * @param string $path
     * @return string
     */
    public static function expand(string $path): string
    {
        if (substr($path, 0, 1) === '~')
        {
            $path = posix_getpwuid(posix_getuid())['dir'] . substr($path, 1);
        }

        return $path;
    }
}
