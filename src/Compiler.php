<?php

namespace Archivr;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Based on the composer counterpart: https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */
class Compiler
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function compile(string $targetFileName): void
    {
        if (file_exists($targetFileName))
        {
            $this->filesystem->remove($targetFileName);
        }


        $phar = new \Phar($targetFileName, 0, 'archivr.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA256);

        $phar->startBuffering();

        $this->addSourceFiles($phar);
        $this->addDependencyFiles($phar);

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../bootstrap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../bin/archivr'));

        $phar->setStub($this->getStub());
        $phar->compressFiles(\Phar::GZ);

        $phar->stopBuffering();
    }

    protected function addSourceFiles(\Phar $phar): void
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in(__DIR__)
        ;
        foreach ($finder as $file)
        {
            $this->addFile($phar, $file);
        }
    }

    protected function addDependencyFiles(\Phar $phar): void
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('LICENSE')
            ->exclude('docs')
            ->exclude('examples')
            ->exclude('Tests')
            ->exclude('tests')
            ->in(__DIR__ . '/../vendor/')
        ;
        foreach ($finder as $file)
        {
            $this->addFile($phar, $file);
        }
    }

    protected function addFile(\Phar $phar, \SplFileInfo $file): void
    {
        $relativePath = $this->getRelativeFilePath($file);
        $content = $this->stripWhitespace(file_get_contents($file->getPathname()));

        $phar->addFromString($relativePath, $content);
    }

    protected function getRelativeFilePath(\SplFileInfo $file): string
    {
        $realPath = $file->getRealPath();
        $pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $pos = strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

        return strtr($relativePath, '\\', '/');
    }

    protected function stripWhitespace(string $source): string
    {
        $output = '';

        foreach (token_get_all($source) as $token)
        {
            if (is_string($token))
            {
                $output .= $token;
            }
            elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT]))
            {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            }
            elseif (T_WHITESPACE === $token[0])
            {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);

                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);

                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);

                $output .= $whitespace;
            }
            else
            {
                $output .= $token[1];
            }
        }

        return $output;
    }

    protected function getStub(): string
    {
        return <<<EOF
#!/usr/bin/env php
<?php

Phar::mapPhar('archivr.phar');

require 'phar://archivr.phar/bin/archivr';

__HALT_COMPILER();
EOF;
    }
}
