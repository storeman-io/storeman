<?php

namespace Storeman;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Compiles the application into a standalone phar which is suitable for distribution.
 * Based on the composer counterpart: https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */
class PharCompiler
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Compiles the phar into the given target file path and the given file mode.
     *
     * @param string $targetFilePath
     * @param int $mode
     */
    public function compile(string $targetFilePath, int $mode = 0744): void
    {
        if (file_exists($targetFilePath))
        {
            $this->filesystem->remove($targetFilePath);
        }


        $phar = new \Phar($targetFilePath, 0, 'storeman.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA256);

        $phar->startBuffering();

        $this->addSourceFiles($phar);
        $this->addDependencyFiles($phar);

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../bootstrap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../bin/storeman'));

        $phar->setStub($this->getStub());
        $phar->compressFiles(\Phar::GZ);

        $phar->stopBuffering();

        $this->filesystem->chmod($targetFilePath, $mode);
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
        $pathPrefix = dirname(__DIR__) . '/';

        $pos = strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

        return strtr($relativePath, '\\', '/');
    }

    /**
     * Reduces file size while preserving line numbers.
     *
     * @param string $source
     * @return string
     */
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

Phar::mapPhar('storeman.phar');

require 'phar://storeman.phar/bin/storeman';

__HALT_COMPILER();
EOF;
    }
}
