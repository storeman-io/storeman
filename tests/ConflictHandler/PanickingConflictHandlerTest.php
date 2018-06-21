<?php

namespace Storeman\Test\ConflictHandler;

use Storeman\ConflictHandler\PanickingConflictHandler;
use Storeman\ConflictHandler\ConflictException;
use Storeman\Test\ConfiguredMockProviderTrait;
use PHPUnit\Framework\TestCase;

class PanickingConflictHandlerTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function testHandleConflict()
    {
        $indexObject = $this->getIndexObjectMock();

        $this->expectException(ConflictException::class);

        $handler = new PanickingConflictHandler();
        $handler->handleConflict($indexObject, $indexObject, $indexObject);
    }
}
