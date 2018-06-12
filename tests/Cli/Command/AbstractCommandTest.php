<?php

namespace Storeman\Test\Cli\Command;

use PHPUnit\Framework\TestCase;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractCommandTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testCallOutsideArchive(array $input = [])
    {
        chdir($this->getTemporaryPathGenerator()->getTemporaryDirectory());

        $tester = new CommandTester($this->getCommand());
        $returnCode = $tester->execute($input);

        $this->assertNotEquals(0, $returnCode);
        $this->assertLessThanOrEqual(1, substr_count($tester->getDisplay(true), "\n"));
    }

    abstract protected function getCommand(): Command;
}
