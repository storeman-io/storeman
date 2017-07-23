<?php

namespace Archivr\Test\Operation;

use Archivr\Connection\StreamConnection;
use Archivr\Operation\DownloadOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
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

        $testVaultConnection = new StreamConnection($testVault->getBasePath());

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $operation = new DownloadOperation($targetFilePath, $testBlobId, $testVaultConnection);
        $operation->execute();

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }
}