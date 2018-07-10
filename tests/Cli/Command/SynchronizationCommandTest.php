<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Storeman;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Storeman\Test\TestVaultGeneratorProviderTrait;
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
        $firstTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($firstConfig));

        $secondTestVault = new TestVault();
        $secondTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($secondConfig));

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

    public function testNestedVaultsSupport()
    {
        $firstConfig = [
            'identity' => 'Someone',
            'vaults' => [
                [
                    'adapter' => 'local',
                    'settings' => [
                        'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory(),
                    ],
                ],
            ],
        ];

        $secondConfig = $firstConfig;
        $secondConfig['identity'] = 'Some other one';

        $firstSubVaultConfig = $firstConfig;
        $firstSubVaultConfig['identity'] = 'The sub one';
        $firstSubVaultConfig['vaults'][0]['settings']['path'] = $this->getTemporaryPathGenerator()->getTemporaryDirectory();

        $secondSubVaultConfig = $firstSubVaultConfig;
        $secondSubVaultConfig['identity'] = 'The second sub one';


        $firstTestVault = new TestVault();
        $firstTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($firstConfig, JSON_PRETTY_PRINT));
        $firstTestVault->touch('firstFile.ext');
        $firstTestVault->mkdir('sub vault');
        $firstTestVault->fwrite('sub vault/' . Storeman::CONFIG_FILE_NAME, json_encode($firstSubVaultConfig, JSON_PRETTY_PRINT));
        $firstTestVault->touch('sub vault/firstSubFile.ext');

        $secondTestVault = new TestVault();
        $secondTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($secondConfig, JSON_PRETTY_PRINT));
        $secondTestVault->touch('secondFile.ext');

        $subTestVault = new TestVault();
        $subTestVault->fwrite(Storeman::CONFIG_FILE_NAME, json_encode($secondSubVaultConfig));
        $subTestVault->touch('secondSubFile.ext');


        $tester = new CommandTester(new SynchronizeCommand());

        $this->assertEquals(0, $tester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));
        $this->assertEquals(0, $tester->execute(['-c' => $secondTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));

        $this->assertTrue(is_file($secondTestVault->getBasePath() . 'firstFile.ext'));
        $this->assertTrue(is_dir($secondTestVault->getBasePath() . 'sub vault'));
        $this->assertTrue(is_file($secondTestVault->getBasePath() . 'sub vault/firstSubFile.ext'));

        $this->assertEquals(0, $tester->execute(['-c' => $firstTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));

        $this->assertTrue(is_file($firstTestVault->getBasePath() . 'secondFile.ext'));

        $this->assertEquals(0, $tester->execute(['-c' => $firstTestVault->getBasePath() . 'sub vault/' . Storeman::CONFIG_FILE_NAME]));
        $this->assertEquals(0, $tester->execute(['-c' => $subTestVault->getBasePath() . Storeman::CONFIG_FILE_NAME]));

        $this->assertTrue(is_file($subTestVault->getBasePath() . 'firstSubFile.ext'));
    }

    protected function getCommand(): Command
    {
        return new SynchronizeCommand();
    }
}
