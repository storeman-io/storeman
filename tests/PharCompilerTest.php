<?php

namespace Storeman\Test;

use PHPUnit\Framework\TestCase;
use Storeman\PharCompiler;
use Symfony\Component\Process\Process;

class PharCompilerTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testCompilation()
    {
        $targetPath = "{$this->getTemporaryPathGenerator()->getTemporaryDirectory()}/storeman.phar";

        $compiler = new PharCompiler();
        $compiler->compile($targetPath);

        $this->assertTrue(is_file($targetPath));

        return $targetPath;
    }

    /**
     * @depends testCompilation
     */
    public function testExecution(string $pharPath)
    {
        $process = new Process(PHP_BINARY . " {$pharPath}");
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertContains('Storeman', $process->getOutput());
    }
}
