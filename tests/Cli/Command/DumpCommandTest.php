<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\DumpCommand;
use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Storeman;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Storeman\Test\TestVaultGeneratorProviderTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\SplFileInfo;

class DumpCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

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

        $testVault = $this->getTestVaultGenerator()->generate();
        $testVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($config));

        $this->assertTrue(chdir($testVault->getBasePath()));

        $returnCode = (new CommandTester(new SynchronizeCommand()))->execute([]);

        $this->assertEquals(0, $returnCode);

        $dumpTarget = new TestVault();

        $tester = new CommandTester(new DumpCommand());
        $returnCode = $tester->execute([
            'path' => $dumpTarget->getBasePath()
        ]);

        $this->assertEquals(0, $returnCode);

        foreach ($testVault as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $this->assertNotNull($dumpTarget->getObjectByRelativePath($fileInfo->getRelativePathname()));
        }
    }

    public function testCallOutsideArchive(array $inputs = [])
    {
        parent::testCallOutsideArchive([
            'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory()
        ]);
    }

    protected function getCommand(): Command
    {
        return new DumpCommand();
    }
}
