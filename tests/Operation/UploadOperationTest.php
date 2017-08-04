<?php

namespace Archivr\Test\Operation;

use Archivr\ConnectionAdapter\PathConnectionAdapter;
use Archivr\Operation\UploadOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
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
        $testVaultConnection = new PathConnectionAdapter($testVault->getBasePath());

        $operation = new UploadOperation($testFilePath, $testBlobId, $testVaultConnection);
        $operation->execute();

        $uploadedFileContent = $testVaultConnection->read($testBlobId);

        $this->assertTrue(is_string($uploadedFileContent));
        $this->assertEquals($testFileContent, $uploadedFileContent);
    }
}
