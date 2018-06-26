<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\ChmodOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class ChmodOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile(0664);

        $operation = new ChmodOperation(basename($testFilePath), 0777);
        $operation->execute(dirname($testFilePath) . '/', $this->getFileReaderMock(), $this->getVaultLayoutMock());

        $this->assertEquals(fileperms($testFilePath) & 0777, 0777);
    }
}
