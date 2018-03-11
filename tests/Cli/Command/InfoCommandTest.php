<?php

namespace Cli\Command;

use Archivr\Cli\Command\InfoCommand;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class InfoCommandTest extends AbstractCommandTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testConfigurationDisplayment()
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
                    'settings' => [
                        'path' => $this->getTemporaryPathGenerator()->getTemporaryDirectory()
                    ],
                ],
            ],
        ];

        $testVault = new TestVault();
        $testVault->fwrite('archivr.json', json_encode($config));

        $tester = new CommandTester(new InfoCommand());
        $returnCode = $tester->execute([
            '-c' => $testVault->getBasePath() . 'archivr.json',
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
