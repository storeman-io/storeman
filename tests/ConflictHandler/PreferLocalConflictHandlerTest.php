<?php

namespace Storeman\Test\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\ConflictHandler\PreferLocalConflictHandler;
use Storeman\Test\ConfiguredMockProviderTrait;

class PreferLocalConflictHandlerTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function testHandleConflict()
    {
        $indexObject = $this->getIndexObjectMock();

        $handler = new PreferLocalConflictHandler();
        $result = $handler->handleConflict($indexObject, $indexObject, $indexObject);

        $this->assertEquals(ConflictHandlerInterface::USE_LOCAL, $result);
    }
}
