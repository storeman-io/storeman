<?php

namespace Storeman\Test\Hash;

use PHPUnit\Framework\TestCase;
use Storeman\Config\Configuration;
use Storeman\FileReader;
use Storeman\Hash\Algorithm\Md5;
use Storeman\Hash\Algorithm\Sha1;
use Storeman\Hash\HashContainer;
use Storeman\Hash\HashProvider;
use Storeman\Test\ConfiguredMockProviderTrait;
use Storeman\Test\TestVault;

class HashProviderTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function test()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World');

        $configuration = new Configuration();
        $configuration->setPath($testVault->getBasePath());
        $configuration->setFileChecksums(['md5']);

        $algorithms = [
            'md5' => new Md5(),
            'sha1' => new Sha1(),
        ];
        $fileReader = new FileReader($configuration, $algorithms);
        $hashProvider = new HashProvider($fileReader, $configuration, $algorithms);

        $hashContainer = new HashContainer();
        $indexObject = $this->getIndexObjectMock([
            'isFile' => true,
            'getRelativePath' => 'file.ext',
            'getHashes' => $hashContainer,
        ]);

        // concrete algorithm intercepted by FileReader
        $this->assertEquals(
            'b10a8db164e0754105b7a99be72e3fe5',
            $hashProvider->getHash($indexObject, 'md5')
        );

        // actually computed by HashProvider
        $this->assertEquals(
            '0a4d55a8d778e5022fab701977c5d840bbc486d0',
            $hashProvider->getHash($indexObject, 'sha1')
        );
    }
}
