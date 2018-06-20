<?php

namespace Storeman\Test\Operation;

use Storeman\Operation\DownloadOperation;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use Ramsey\Uuid\Uuid;
use Storeman\VaultLayout\VaultLayoutInterface;

class DownloadOperationTest extends AbstractOperationTest
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        $testVault = new TestVault();
        $testVault->fwrite($testBlobId, $testFileContent);

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $vaultLayoutMock = $this->createMock(VaultLayoutInterface::class);
        $vaultLayoutMock
            ->expects($this->once())
            ->method('readBlob')
            ->willReturn($this->getReadableStream($testFileContent));

        /** @var VaultLayoutInterface $vaultLayoutMock */

        $operation = new DownloadOperation(basename($targetFilePath), $testBlobId);
        $operation->execute(
            dirname($targetFilePath) . DIRECTORY_SEPARATOR, $this->getFileReaderMock(), $vaultLayoutMock
        );

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }

    /**
     * @param string $content
     * @return resource
     */
    protected function getReadableStream(string $content)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }
}
