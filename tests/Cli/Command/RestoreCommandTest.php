<?php

namespace Cli\Command;

use Archivr\Cli\Command\RestoreCommand;
use Archivr\Cli\Command\SynchronizeCommand;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
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
                    'storage' => 'local',
                    'settings' => [
                        'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory(),
                    ],
                ],
            ],
        ];

        $originalContent = md5(rand());

        $testVault = new TestVault();
        $testVault->fwrite('archivr.json', json_encode($config));
        $testVault->fwrite('test.ext', $originalContent);

        $this->assertTrue(chdir($testVault->getBasePath()));

        $tester = new CommandTester(new SynchronizeCommand());
        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        $testVault->fwrite('test.ext', 'Replaced');

        $tester = new CommandTester(new RestoreCommand());
        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        $this->assertEquals($originalContent, file_get_contents($testVault->getBasePath() . 'test.ext'));
    }

    protected function getCommand(): Command
    {
        return new RestoreCommand();
    }
}
