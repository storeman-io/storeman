<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\ChmodOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;
use Storeman\VaultLayout\VaultLayoutInterface;

class ChmodOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile(0664);

        $operation = new ChmodOperation(basename($testFilePath), 0777);
        $operation->execute(dirname($testFilePath) . DIRECTORY_SEPARATOR, $this->createMock(VaultLayoutInterface::class));

        $this->assertEquals(fileperms($testFilePath) & 0777, 0777);
    }
}
