<?php

namespace Storeman\Test\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PreferLocalConflictHandler;
use Storeman\IndexObject;
use Storeman\Test\TemporaryPathGenerator;

class PreferLocalConflictHandlerTest extends TestCase
{
    public function testHandleConflict()
    {
        $tempPathGenerator = new TemporaryPathGenerator();
        $filePath = $tempPathGenerator->getTemporaryFile();

        $indexObject = IndexObject::fromPath(dirname($filePath), basename($filePath));

        $handler = new PreferLocalConflictHandler();
        $result = $handler->handleConflict($indexObject, $indexObject, $indexObject);

        $this->assertEquals(ConflictHandlerInterface::USE_LOCAL, $result);
    }
}
