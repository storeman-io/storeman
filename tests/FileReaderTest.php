<?php

namespace Storeman\Test;

use PHPUnit\Framework\TestCase;
use Storeman\FileReader;
use Storeman\Hash\Algorithm\Md5;
use Storeman\Hash\Algorithm\Sha1;
use Storeman\Hash\Algorithm\Sha256;
use Storeman\Hash\HashContainer;

class FileReaderTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function test()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World');

        $configuration = $this->getConfigurationMock([
            'getPath' => $testVault->getBasePath(),
            'getFileChecksums' => ['sha256', 'sha1', 'md5'],
        ]);

        $fileReader = new FileReader($configuration, [
            'sha1' => new Sha1(),
            'sha256' => new Sha256(),
            'md5' => new Md5(),
        ]);

        $stream = $fileReader->getReadStream($this->getIndexObjectMock([
            'isFile' => true,
            'getRelativePath' => 'file.ext',
            'getHashes' => $hashContainer = new HashContainer(),
        ]));

        $this->assertTrue(is_resource($stream));
        $this->assertEquals('Hello World', stream_get_contents($stream));
        $this->assertEquals('0a4d55a8d778e5022fab701977c5d840bbc486d0', $hashContainer->getHash('sha1'));
        $this->assertEquals('a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e', $hashContainer->getHash('sha256'));
        $this->assertEquals('b10a8db164e0754105b7a99be72e3fe5', $hashContainer->getHash('md5'));

        fclose($stream);
    }
}
