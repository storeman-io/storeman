<?php

namespace Archivr\Test\Operation;

use Archivr\ConnectionAdapter\FlysystemConnectionAdapter;
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
        $testVaultConnection = new FlysystemConnectionAdapter(new Filesystem(new Local($testVault->getBasePath())));

        $operation = new UploadOperation($testFilePath, $testBlobId, $testVaultConnection);
        $operation->execute();

        $uploadedFileContent = $testVaultConnection->read($testBlobId);

        $this->assertTrue(is_string($uploadedFileContent));
        $this->assertEquals($testFileContent, $uploadedFileContent);
    }
}
