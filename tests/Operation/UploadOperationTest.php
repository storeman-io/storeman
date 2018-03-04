<?php

namespace Archivr\Test\Operation;

use Archivr\StorageDriver\FlysystemStorageDriver;
use Archivr\Operation\UploadOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UploadOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testExecution()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        file_put_contents($testFilePath, $testFileContent);

        $testVault = new TestVault();
        $testVaultConnection = new FlysystemStorageDriver(new Filesystem(new Local($testVault->getBasePath())));

        $operation = new UploadOperation(basename($testFilePath), $testBlobId);
        $operation->execute(dirname($testFilePath) . DIRECTORY_SEPARATOR, $testVaultConnection);

        $uploadedFileContent = $testVaultConnection->read($testBlobId);

        $this->assertTrue(is_string($uploadedFileContent));
        $this->assertEquals($testFileContent, $uploadedFileContent);
    }

    public function testExecutionWithFilter()
    {
        $testFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        file_put_contents($testFilePath, str_rot13($testFileContent));

        $testVault = new TestVault();
        $testVaultConnection = new FlysystemStorageDriver(new Filesystem(new Local($testVault->getBasePath())));

        $operation = new UploadOperation(basename($testFilePath), $testBlobId, [
            'string.rot13' => []
        ]);
        $operation->execute(dirname($testFilePath) . DIRECTORY_SEPARATOR, $testVaultConnection);

        $uploadedFileContent = $testVaultConnection->read($testBlobId);

        $this->assertTrue(is_string($uploadedFileContent));
        $this->assertEquals($testFileContent, $uploadedFileContent);
    }
}
