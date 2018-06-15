<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\UnlinkOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;
use Storeman\VaultLayout\VaultLayoutInterface;

class UnlinkOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $this->assertTrue(is_file($tempFile));

        $operation = new UnlinkOperation(basename($tempFile));
        $operation->execute(dirname($tempFile) . DIRECTORY_SEPARATOR, $this->createMock(VaultLayoutInterface::class));

        $this->assertFalse(is_file($tempFile));
    }
}
