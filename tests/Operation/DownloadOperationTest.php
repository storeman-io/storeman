<?php

namespace Archivr\Test\Operation;

use Archivr\Operation\DownloadOperation;
use Archivr\StorageAdapter\LocalStorageAdapter;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use Archivr\VaultConfiguration;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DownloadOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        $testVault = new TestVault();
        $testVault->fwrite($testBlobId, $testFileContent);

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $operation = new DownloadOperation(basename($targetFilePath), $testBlobId);
        $operation->execute(dirname($targetFilePath) . DIRECTORY_SEPARATOR, $this->getLocalStorageAdapter($testVault->getBasePath()));

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }

    public function testExecutionWithFilter()
    {
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        $testVault = new TestVault();
        $testVault->fwrite($testBlobId, str_rot13($testFileContent));

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $operation = new DownloadOperation(basename($targetFilePath), $testBlobId, [
            'string.rot13' => []
        ]);
        $operation->execute(dirname($targetFilePath) . DIRECTORY_SEPARATOR, $this->getLocalStorageAdapter($testVault->getBasePath()));

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }

    protected function getLocalStorageAdapter(string $path): LocalStorageAdapter
    {
        $vaultConfig = new VaultConfiguration();
        $vaultConfig->setSetting('path', $path);

        return new LocalStorageAdapter($vaultConfig);
    }
}
