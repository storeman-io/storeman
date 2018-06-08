<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\SymlinkOperation;
use Storeman\StorageAdapter\StorageAdapterInterface;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class SymlinkOperationTest extends TestCase
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
        $operation->execute($tempDir, $this->createMock(StorageAdapterInterface::class));

        $this->assertTrue(is_link($absoluteLinkPath));
        $this->assertEquals(0754, fileperms($absoluteLinkPath) & 0777);
        $this->assertEquals($absoluteTargetPath, readlink($absoluteLinkPath));
    }
}
