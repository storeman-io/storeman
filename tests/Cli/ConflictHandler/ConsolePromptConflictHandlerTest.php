<?php

namespace Storeman\Test\Cli\ConflictHandler;

use PHPUnit\Framework\TestCase;
use Storeman\Cli\ConflictHandler\ConsolePromptConflictHandler;
use Storeman\Cli\ConsoleStyle;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\IndexObject;
use Storeman\Test\ConfiguredMockProviderTrait;

class ConsolePromptConflictHandlerTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function test()
    {
        /** @var IndexObject $indexObject */
        $indexObject = $this->createConfiguredMock(IndexObject::class, ['getRelativePath' => 'test/path.ext']);

        $this->assertEquals(
            ConflictHandlerInterface::USE_LOCAL,
            (new ConsolePromptConflictHandler($this->getConsoleStyleMock(['choice' => 'l', 'getInput' => $this->getInputMock(['isInteractive' => true])])))->handleConflict($indexObject)
        );

        $this->assertEquals(
            ConflictHandlerInterface::USE_REMOTE,
            (new ConsolePromptConflictHandler($this->getConsoleStyleMock(['choice' => 'r', 'getInput' => $this->getInputMock(['isInteractive' => true])])))->handleConflict($indexObject)
        );
    }

    public function testInvalidChoice()
    {
        $consoleStyle = $this->createMock(ConsoleStyle::class);
        $consoleStyle
            ->expects($this->exactly(3))
            ->method('choice')
            ->willReturnOnConsecutiveCalls('', 'x', 'l');
        $consoleStyle->method('getInput')->willReturn($this->getInputMock(['isInteractive' => true]));

        $this->assertEquals(
            ConflictHandlerInterface::USE_LOCAL,
            (new ConsolePromptConflictHandler($consoleStyle))->handleConflict(
                $this->createConfiguredMock(IndexObject::class, ['getRelativePath' => 'test/path.ext'])
            )
        );
    }
}
