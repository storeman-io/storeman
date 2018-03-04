<?php

namespace Archivr\Test;

use Archivr\TildeExpansion;
use PHPUnit\Framework\TestCase;

class TildeExpansionTest extends TestCase
{
    public function testTildeExpansion()
    {
        $currentUserHome = posix_getpwuid(posix_getuid())['dir'];

        $this->assertEquals('/tmp/test', TildeExpansion::expand('/tmp/test'));
        $this->assertEquals($currentUserHome . DIRECTORY_SEPARATOR . 'test', TildeExpansion::expand('~/test'));
    }
}
