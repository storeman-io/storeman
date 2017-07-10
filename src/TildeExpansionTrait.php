<?php

namespace Archivr;

trait TildeExpansionTrait
{
    private function expandTildePath(string $path): string
    {
        if (substr($path, 0, 1) === '~')
        {
            $path = posix_getpwuid(posix_getuid())['dir'] . substr($path, 1);
        }

        return $path;
    }
}