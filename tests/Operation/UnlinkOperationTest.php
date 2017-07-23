<?php

namespace Archivr\Test\Operation;

use Archivr\Operation\UnlinkOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use PHPUnit\Framework\TestCase;

class UnlinkOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $tempFile = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $this->assertTrue(is_file($tempFile));

        $operation = new UnlinkOperation($tempFile);
        $operation->execute();

        $this->assertFalse(is_file($tempFile));
    }
}