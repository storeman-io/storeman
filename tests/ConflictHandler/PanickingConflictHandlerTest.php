<?php

namespace Storeman\Test\ConflictHandler;

use Storeman\ConflictHandler\PanickingConflictHandler;
use Storeman\Exception\ConflictException;
use Storeman\IndexObject;
use Storeman\Test\TemporaryPathGenerator;
use PHPUnit\Framework\TestCase;

class PanickingConflictHandlerTest extends TestCase
{
    public function testHandleConflict()
    {
        $tempPathGenerator = new TemporaryPathGenerator();
        $filePath = $tempPathGenerator->getTemporaryFile();

        $indexObject = IndexObject::fromPath(dirname($filePath), basename($filePath));

        $this->expectException(ConflictException::class);

        $handler = new PanickingConflictHandler();
        $handler->handleConflict($indexObject, $indexObject, $indexObject);
    }
}
