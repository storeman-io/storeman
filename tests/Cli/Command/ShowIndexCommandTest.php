<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\ShowIndexCommand;
use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Storeman;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ShowIndexCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function test()
    {
        $vaultPath = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $firstConfig = [
            'identity' => 'Someone',
            'vaults' => [
                [
                    'adapter' => 'local',
                    'settings' => [
                        'path' => $vaultPath,
                    ],
                ],
            ],
        ];

        $testVault = new TestVault();
        $testVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($firstConfig));

        $showIndexCommandTester = new CommandTester($this->getCommandWithApplication());
        $syncCommandTester = new CommandTester($this->getCommandWithApplication(new SynchronizeCommand()));

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath()]));
        $this->assertContains('not find any past synchronizations', $showIndexCommandTester->getDisplay());

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath(), 'revision' => 'local']));
        $this->assertContains('Empty', $showIndexCommandTester->getDisplay());

        $testVault->touch('file.ext');
        $testVault->mkdir('some dir');

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath(), 'revision' => 'local']));
        $this->assertContains('file.ext', $showIndexCommandTester->getDisplay());
        $this->assertContains('some dir', $showIndexCommandTester->getDisplay());

        $this->assertEquals(0, $syncCommandTester->execute(['-c' => $testVault->getBasePath()]));

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath()]));
        $this->assertContains('file.ext', $showIndexCommandTester->getDisplay());
        $this->assertContains('some dir', $showIndexCommandTester->getDisplay());

        $testVault->touch('new.ext');

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath()]));
        $this->assertNotContains('new.ext', $showIndexCommandTester->getDisplay());

        $this->assertEquals(0, $showIndexCommandTester->execute(['-c' => $testVault->getBasePath(), 'revision' => 'local']));
        $this->assertContains('file.ext', $showIndexCommandTester->getDisplay());
        $this->assertContains('some dir', $showIndexCommandTester->getDisplay());
        $this->assertContains('new.ext', $showIndexCommandTester->getDisplay());
    }

    protected function getCommand(): Command
    {
        return new ShowIndexCommand();
    }
}
