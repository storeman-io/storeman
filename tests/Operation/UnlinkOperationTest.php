<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\UnlinkOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class UnlinkOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $this->assertTrue(is_file($tempFile));

        $operation = new UnlinkOperation(basename($tempFile));
        $operation->execute(dirname($tempFile) . DIRECTORY_SEPARATOR, $this->getFileReaderMock(), $this->getVaultLayoutMock());

        $this->assertFalse(is_file($tempFile));
    }
}
