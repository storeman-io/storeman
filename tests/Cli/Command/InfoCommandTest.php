<?php

namespace Storeman\Test\Cli\Command;

use Storeman\Cli\Command\InfoCommand;
use Storeman\Cli\Command\SynchronizeCommand;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function test()
    {
        $config = [
            'exclude' => [
                'some/file.ext',
                'a/deep/path'
            ],
            'identity' => 'My Identity',
            'vaults' => [
                [
                    'title' => 'Some Vault Title',
                    'adapter' => 'local',
                    'vaultLayout' => 'amberjack',
                    'settings' => [
                        'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory()
                    ],
                ],
            ],
        ];

        $testVault = new TestVault();
        $testVault->fwrite('storeman.json', json_encode($config));

        $absoluteConfigFilePath = $testVault->getBasePath() . 'storeman.json';

        // ensure existing history
        $tester = new CommandTester(new SynchronizeCommand());
        $tester->execute([
            '-c' => $absoluteConfigFilePath,
        ]);

        $tester = new CommandTester(new InfoCommand());
        $returnCode = $tester->execute([
            '-c' => $absoluteConfigFilePath,
        ]);
        $output = $tester->getDisplay(true);

        $this->assertEquals(0, $returnCode);
        $this->assertContains(rtrim($testVault->getBasePath(), DIRECTORY_SEPARATOR), $output);
        $this->assertContains($config['identity'], $output);

        foreach ($config['exclude'] as $excludedPath)
        {
            $this->assertContains($excludedPath, $output);
        }

        foreach ($config['vaults'] as $vaultConfig)
        {
            $this->assertContains($vaultConfig['title'], $output);
            $this->assertContains($vaultConfig['vaultLayout'], $output);

            foreach ($vaultConfig['settings'] as $key => $value)
            {
                $this->assertContains($key, $output);
                $this->assertContains($value, $output);
            }
        }
    }

    protected function getCommand(): Command
    {
        return new InfoCommand();
    }
}
