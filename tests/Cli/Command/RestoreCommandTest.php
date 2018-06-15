<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\RestoreCommand;
use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Storeman\Vault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RestoreCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function test()
    {
        $config = [
            'vaults' => [
                [
                    'adapter' => 'local',
                    'settings' => [
                        'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory(),
                    ],
                ],
            ],
        ];

        $originalContent = md5(rand());

        $testVault = new TestVault();
        $testVault->fwrite(Vault::CONFIG_FILE_NAME, json_encode($config));
        $testVault->fwrite('test.ext', $originalContent);

        $this->assertTrue(chdir($testVault->getBasePath()));

        $tester = new CommandTester(new SynchronizeCommand());
        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        $testVault->fwrite('test.ext', 'Replaced');
        $testVault->mkdir('test.dir');

        $tester = new CommandTester(new RestoreCommand());
        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        $this->assertEquals($originalContent, file_get_contents($testVault->getBasePath() . 'test.ext'));
        $this->assertFalse(is_dir($testVault->getBasePath() . 'test.dir'));
    }

    protected function getCommand(): Command
    {
        return new RestoreCommand();
    }
}
