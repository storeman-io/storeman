<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\SymlinkOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class SymlinkOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempDir = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $targetName = 'myTarget';
        $linkName = 'test';

        $absoluteTargetPath = $tempDir . $targetName;
        $absoluteLinkPath = $tempDir . $linkName;

        $this->assertTrue(touch($absoluteTargetPath));

        $operation = new SymlinkOperation($linkName, $targetName, 0754);
        $operation->execute($tempDir, $this->getFileReaderMock(), $this->getVaultLayoutMock());

        $this->assertTrue(is_link($absoluteLinkPath));
        $this->assertEquals(0754, fileperms($absoluteLinkPath) & 0777);
        $this->assertEquals($absoluteTargetPath, readlink($absoluteLinkPath));
    }
}
