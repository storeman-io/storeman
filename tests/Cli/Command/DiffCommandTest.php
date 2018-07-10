<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\DiffCommand;
use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Storeman;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DiffCommandTest extends AbstractCommandTest
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
        $secondConfig = $firstConfig;
        $secondConfig['identity'] = 'Some other one';

        $firstTestVault = new TestVault();
        $firstTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($firstConfig));

        $secondTestVault = new TestVault();
        $secondTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($secondConfig));
        $secondTestVault->touch('fileB.ext');

        $diffCommandTester = new CommandTester($this->getCommandWithApplication());
        $syncCommandTester = new CommandTester($this->getCommandWithApplication(new SynchronizeCommand()));

        $this->assertEquals(0, $diffCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));
        $this->assertContains('any past', $diffCommandTester->getDisplay());

        $this->assertEquals(0, $syncCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));

        $this->assertEquals(0, $diffCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));
        $this->assertContains('No diff', $diffCommandTester->getDisplay());

        $this->assertEquals(1, $diffCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME, 'revision' => 2]));
        $this->assertContains('not find', $diffCommandTester->getDisplay());

        $firstTestVault->touch('fileA.ext');

        $this->assertEquals(0, $diffCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));
        $this->assertContains('fileA.ext', $diffCommandTester->getDisplay());

        $this->assertEquals(0, $syncCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));

        $this->assertEquals(0, $diffCommandTester->execute(['-c' => $secondTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));
        $this->assertContains('fileA.ext', $diffCommandTester->getDisplay());
        $this->assertContains('fileB.ext', $diffCommandTester->getDisplay());

        $this->assertEquals(0, $diffCommandTester->execute(['-c' => $secondTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME, 'compareTo' => 1]));
        $this->assertContains('fileA.ext', $diffCommandTester->getDisplay());
        $this->assertNotContains('fileB.ext', $diffCommandTester->getDisplay());

        $this->assertEquals(1, $diffCommandTester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME, 'compareTo' => 'asd']));
        $this->assertContains('argument', $diffCommandTester->getDisplay());
    }

    protected function getCommand(): Command
    {
        return new DiffCommand();
    }
}
