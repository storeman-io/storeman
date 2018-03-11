<?php

namespace Archivr\Test;

use Archivr\ArrayUtils;
use PHPUnit\Framework\TestCase;

class ArrayUtilsTest extends TestCase
{
    public function testRecursiveArrayDiff()
    {
        $this->assertEquals([], ArrayUtils::recursiveArrayDiff([], []));
        $this->assertEquals([], ArrayUtils::recursiveArrayDiff([1], [1]));
        $this->assertEquals([], ArrayUtils::recursiveArrayDiff([1], [1,2,3]));
        $this->assertEquals([1], ArrayUtils::recursiveArrayDiff([1], []));
        $this->assertEquals([1], ArrayUtils::recursiveArrayDiff([1], [2,3]));
        $this->assertEquals([1,2], ArrayUtils::recursiveArrayDiff([1,2], [3]));

        $this->assertEquals([], ArrayUtils::recursiveArrayDiff([[]], [[]]));
        $this->assertEquals([[]], ArrayUtils::recursiveArrayDiff([[]], []));

        $this->assertEquals(['a' => ['b' => 4231]], ArrayUtils::recursiveArrayDiff(['a' => ['b' => 4231]], ['a' => ['b' => 1234]]));
        $this->assertEquals(['a' => ['b' => 4231]], ArrayUtils::recursiveArrayDiff(['a' => ['b' => 4231, 'c' => 1]], ['a' => ['b' => 1234, 'c' => 1]]));
    }
}
