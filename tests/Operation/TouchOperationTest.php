<?php

namespace Storeman\Test\Operation;

use Storeman\FilesystemUtility;
use Storeman\Operation\TouchOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class TouchOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $mtime = 4231.1234;

        $this->assertNotEquals($mtime, FilesystemUtility::lstat($tempFile)['mtime']);

        $operation = new TouchOperation(basename($tempFile), $mtime);
        $operation->execute(dirname($tempFile) . '/', $this->getFileReaderMock(), $this->getVaultLayoutMock());

        $this->assertEquals($mtime, FilesystemUtility::lstat($tempFile)['mtime']);
    }
}
