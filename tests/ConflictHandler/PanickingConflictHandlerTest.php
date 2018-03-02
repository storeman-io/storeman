<?php

namespace Archivr\Test\ConflictHandler;

use Archivr\ConflictHandler\PanickingConflictHandler;
use Archivr\Exception\ConflictException;
use Archivr\IndexObject;
use Archivr\Test\TemporaryPathGenerator;
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
