<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\TouchOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;

class TouchOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $originalMTime = time() - 100;
        $newMTime = time() - 50;

        touch($tempFile, $originalMTime);

        $this->assertEquals($originalMTime, filemtime($tempFile));

        $operation = new TouchOperation(basename($tempFile), $newMTime);
        $operation->execute(dirname($tempFile) . '/', $this->getFileReaderMock(), $this->getVaultLayoutMock());

        clearstatcache(null, $tempFile);

        $this->assertEquals($newMTime, filemtime($tempFile));
    }
}
