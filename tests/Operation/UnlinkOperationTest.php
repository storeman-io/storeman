<?php

namespace Archivr\Test\Operation;

use Archivr\Operation\UnlinkOperation;
use Archivr\StorageDriver\StorageDriverInterface;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class UnlinkOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $this->assertTrue(is_file($tempFile));

        $operation = new UnlinkOperation(basename($tempFile));
        $operation->execute(dirname($tempFile) . DIRECTORY_SEPARATOR, $this->createMock(StorageDriverInterface::class));

        $this->assertFalse(is_file($tempFile));
    }
}
