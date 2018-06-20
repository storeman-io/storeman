<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\MkdirOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class MkdirOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempDir = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $newDirName = 'Test Directory';
        $absolutePath = $tempDir . DIRECTORY_SEPARATOR . $newDirName;

        $operation = new MkdirOperation($newDirName, 0754);
        $operation->execute($tempDir . DIRECTORY_SEPARATOR, $this->getFileReaderMock(), $this->getVaultLayoutMock());

        $this->assertTrue(is_dir($absolutePath));
        $this->assertEquals(0754, fileperms($absolutePath) & 0777);
    }
}
