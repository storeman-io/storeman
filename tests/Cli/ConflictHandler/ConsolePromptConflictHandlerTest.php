<?php

namespace Storeman\Test\Cli\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\Cli\ConflictHandler\ConsolePromptConflictHandler;
use Storeman\Cli\ConsoleStyle;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\IndexObject;

class ConsolePromptConflictHandlerTest extends TestCase
{
    public function test()
    {
        /** @var IndexObject $indexObject */
        $indexObject = $this->createConfiguredMock(IndexObject::class, ['getRelativePath' => 'test/path.ext']);

        $this->assertEquals(
            ConflictHandlerInterface::USE_LOCAL,
            (new ConsolePromptConflictHandler($this->createConfiguredMock(ConsoleStyle::class, ['choice' => 'l'])))->handleConflict($indexObject)
        );

        $this->assertEquals(
            ConflictHandlerInterface::USE_REMOTE,
            (new ConsolePromptConflictHandler($this->createConfiguredMock(ConsoleStyle::class, ['choice' => 'r'])))->handleConflict($indexObject)
        );
    }

    public function testInvalidChoice()
    {
        $consoleStyle = $this->createMock(ConsoleStyle::class);
        $consoleStyle
            ->expects($this->exactly(3))
            ->method('choice')
            ->willReturnOnConsecutiveCalls('', 'x', 'l');

        $this->assertEquals(
            ConflictHandlerInterface::USE_LOCAL,
            (new ConsolePromptConflictHandler($consoleStyle))->handleConflict(
                $this->createConfiguredMock(IndexObject::class, ['getRelativePath' => 'test/path.ext'])
            )
        );
    }
}
