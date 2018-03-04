<?php

namespace Archivr\Test\Operation;

use Archivr\ConnectionAdapter\DummyConnectionAdapter;
use Archivr\Operation\MkdirOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
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
        $operation->execute($tempDir . DIRECTORY_SEPARATOR, new DummyConnectionAdapter());

        $this->assertTrue(is_dir($absolutePath));
        $this->assertEquals(0754, fileperms($absolutePath) & 0777);
    }
}
