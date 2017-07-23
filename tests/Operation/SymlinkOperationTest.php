<?php

namespace Archivr\Test\Operation;

use Archivr\Operation\SymlinkOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class SymlinkOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempDir = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $target = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $linkName = 'test';
        $absoluteLinkPath = $tempDir . DIRECTORY_SEPARATOR . $linkName;

        $operation = new SymlinkOperation($absoluteLinkPath, $target, 0754);
        $operation->execute();

        $this->assertTrue(is_link($absoluteLinkPath));
        $this->assertEquals(0754, fileperms($absoluteLinkPath) & 0777);
        $this->assertEquals($target, readlink($absoluteLinkPath));
    }
}