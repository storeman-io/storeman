<?php

namespace Archivr\Test\Operation;

use Archivr\ConnectionAdapter\DummyConnectionAdapter;
use Archivr\Operation\ChmodOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class ChmodOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile(0664);

        $operation = new ChmodOperation(basename($testFilePath), 0777);
        $operation->execute(dirname($testFilePath) . DIRECTORY_SEPARATOR, new DummyConnectionAdapter());

        $this->assertEquals(fileperms($testFilePath) & 0777, 0777);
    }
}
