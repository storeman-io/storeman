<?php

namespace Archivr\Test;

use Archivr\TildeExpansionTrait;
use PHPUnit\Framework\TestCase;

class TildeExpansionTest extends TestCase
{
    use TildeExpansionTrait;

    public function testTildeExpansion()
    {
        $currentUserHome = posix_getpwuid(posix_getuid())['dir'];

        $this->assertEquals('/tmp/test', $this->expandTildePath('/tmp/test'));
        $this->assertEquals($currentUserHome . DIRECTORY_SEPARATOR . 'test', $this->expandTildePath('~/test'));
    }
}
