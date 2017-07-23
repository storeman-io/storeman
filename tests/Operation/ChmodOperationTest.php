<?php

namespace Archivr\Test\Operation;

use Archivr\Operation\ChmodOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class ChmodOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile(0664);

        $operation = new ChmodOperation($testFilePath, 0777);
        $operation->execute();

        $this->assertEquals(fileperms($testFilePath) & 0777, 0777);
    }
}