<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\TouchOperation;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class TouchOperationTest extends TestCase
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
        $operation->execute(dirname($tempFile) . DIRECTORY_SEPARATOR, $this->createMock(StorageAdapterInterface::class));

        clearstatcache(null, $tempFile);

        $this->assertEquals($newMTime, filemtime($tempFile));
    }
}
