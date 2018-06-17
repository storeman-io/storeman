<?php

namespace Storeman\Test\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PreferRemoteConflictHandler;
use Storeman\Index\IndexObject;
use Storeman\Test\TemporaryPathGenerator;

class PreferRemoteConflictHandlerTest extends TestCase
{
    public function testHandleConflict()
    {
        $tempPathGenerator = new TemporaryPathGenerator();
        $filePath = $tempPathGenerator->getTemporaryFile();

        $indexObject = IndexObject::fromPath(dirname($filePath), basename($filePath));

        $handler = new PreferRemoteConflictHandler();
        $result = $handler->handleConflict($indexObject, $indexObject, $indexObject);

        $this->assertEquals(ConflictHandlerInterface::USE_REMOTE, $result);
    }
}
