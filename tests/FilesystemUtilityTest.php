<?php

namespace Storeman\Test;

use PHPUnit\Framework\TestCase;
use Storeman\FilesystemUtility;

class FilesystemUtilityTest extends TestCase
{
    public function testParsing()
    {
        $result = FilesystemUtility::parseTime('2018-07-11 00:40:23.100112915 +0200');

        $this->assertEquals(1531262423, floor($result));
        $this->assertEquals(0.100112915, $result - floor($result), '', 0.000001); // test microsecond precision
        $this->assertEquals(1531262423.100112915, $result);
    }

    public function testBuilding()
    {
        $this->assertEquals('2018-07-10 22:40:23.100112915 +0000', FilesystemUtility::buildTime(1531262423.100112915));
        $this->assertEquals('2018-07-11 00:40:23.100112915 +0200', FilesystemUtility::buildTime(1531262423.100112915, 9, new \DateTimeZone('Europe/Berlin')));
    }

    /**
     * @depends testParsing
     */
    public function testLstat()
    {
        foreach ([PHP_BINARY, PHP_BINDIR, sys_get_temp_dir()] as $path)
        {
            $stat = FilesystemUtility::lstat($path);
            $ref = lstat($path);

            $keys = array_diff(array_keys($ref), ['atime', 'mtime', 'ctime', 'blksize', 8, 9, 10, 11]);

            $this->assertEquals(
                array_intersect_key($ref, array_flip($keys)),
                array_intersect_key($stat, array_flip($keys))
            );

            $this->assertEquals($ref['atime'], floor($stat['atime']));
            $this->assertEquals($ref['mtime'], floor($stat['mtime']));
            $this->assertEquals($ref['ctime'], floor($stat['ctime']));
            $this->assertEquals($ref['atime'], floor($stat[8]));
            $this->assertEquals($ref['mtime'], floor($stat[9]));
            $this->assertEquals($ref['ctime'], floor($stat[10]));

            // do not compare blksize as its unreliable
            // see https://unix.stackexchange.com/questions/14409/difference-between-block-size-and-cluster-size/14411#14411
        }
    }

    public function testInvalidLstat()
    {
        $this->expectException(\RuntimeException::class);

        FilesystemUtility::lstat('/non/existing');
    }
}
