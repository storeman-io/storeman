<?php

namespace Storeman\Test\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PreferRemoteConflictHandler;
use Storeman\Test\ConfiguredMockProviderTrait;

class PreferRemoteConflictHandlerTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function testHandleConflict()
    {
        $indexObject = $this->getIndexObjectMock();

        $handler = new PreferRemoteConflictHandler();
        $result = $handler->handleConflict($indexObject, $indexObject, $indexObject);

        $this->assertEquals(ConflictHandlerInterface::USE_REMOTE, $result);
    }
}
