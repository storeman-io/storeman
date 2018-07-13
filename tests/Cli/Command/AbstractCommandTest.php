<?php

namespace Storeman\Test\Cli\Command;

use PHPUnit\Framework\TestCase;
use Storeman\Cli\Application;
use Storeman\Test\TemporaryPathGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractCommandTest extends TestCase
{
    public function testCallOutsideArchive(array $input = [])
    {
        $tmpPathGenerator = new TemporaryPathGenerator();

        chdir($tmpPathGenerator->getTemporaryDirectory());

        $tester = new CommandTester($this->getCommand());
        $returnCode = $tester->execute($input);

        $this->assertNotEquals(0, $returnCode);
        $this->assertLessThanOrEqual(1, substr_count($tester->getDisplay(true), "\n"));
    }

    protected function getCommandWithApplication(Command $command = null): Command
    {
        $command = $command ?: $this->getCommand();
        $command->setApplication(new Application());

        return $command;
    }

    abstract protected function getCommand(): Command;
}
