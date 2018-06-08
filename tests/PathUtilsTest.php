<?php

namespace Storeman\Test;

use Storeman\PathUtils;
use PHPUnit\Framework\TestCase;

class PathUtilsTest extends TestCase
{
    public function testTildeExpansion()
    {
        $currentUserHome = posix_getpwuid(posix_getuid())['dir'];

        $this->assertEquals('test', PathUtils::expandTilde('test'));
        $this->assertEquals('./test', PathUtils::expandTilde('./test'));
        $this->assertEquals('/tmp/test', PathUtils::expandTilde('/tmp/test'));
        $this->assertEquals($currentUserHome . DIRECTORY_SEPARATOR . 'test', PathUtils::expandTilde('~/test'));
    }

    /**
     * @depends testTildeExpansion
     */
    public function testGetAbsolutePath()
    {
        $wd = getcwd();

        $this->assertEquals("{$wd}/", PathUtils::getAbsolutePath(''));
        $this->assertEquals("{$wd}/", PathUtils::getAbsolutePath('.'));
        $this->assertEquals("{$wd}/test", PathUtils::getAbsolutePath('test'));
        $this->assertEquals("{$wd}/test/123", PathUtils::getAbsolutePath('test/123'));
        $this->assertEquals("/some/test/path", PathUtils::getAbsolutePath('/some/test/path'));
    }
}
