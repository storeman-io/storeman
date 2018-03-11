<?php

namespace Cli\Command;

use Archivr\Cli\Command\SynchronizeCommand;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use Archivr\Test\TestVaultGeneratorProviderTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\SplFileInfo;

class SynchronizationCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;
    use TestVaultGeneratorProviderTrait;

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

        $firstTestVault = $this->getTestVaultGenerator()->generate();
        $firstTestVault->fwrite('archivr.json', json_encode($firstConfig));

        $secondTestVault = new TestVault();
        $secondTestVault->fwrite('archivr.json', json_encode($secondConfig));

        $tester = new CommandTester(new SynchronizeCommand());

        $this->assertTrue(chdir($firstTestVault->getBasePath()));

        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        $this->assertTrue(chdir($secondTestVault->getBasePath()));

        $returnCode = $tester->execute([]);

        $this->assertEquals(0, $returnCode);

        foreach ($firstTestVault as $fileInfo)
        {
            /** @var SplFileInfo $fileInfo */

            $this->assertNotNull($secondTestVault->getObjectByRelativePath($fileInfo->getRelativePathname()));
        }
    }

    protected function getCommand(): Command
    {
        return new SynchronizeCommand();
    }
}
