<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\MkdirOperation;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class MkdirOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempDir = $this->getTemporaryPathGenerator()->getTemporaryDirectory();
        $newDirName = 'Test Directory';
        $absolutePath = $tempDir . DIRECTORY_SEPARATOR . $newDirName;

        $operation = new MkdirOperation($newDirName, 0754);
        $operation->execute($tempDir . DIRECTORY_SEPARATOR, $this->createMock(StorageAdapterInterface::class));

        $this->assertTrue(is_dir($absolutePath));
        $this->assertEquals(0754, fileperms($absolutePath) & 0777);
    }
}
