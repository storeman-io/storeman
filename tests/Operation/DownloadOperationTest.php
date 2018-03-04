<?php

namespace Archivr\Test\Operation;

use Archivr\ConnectionAdapter\FlysystemConnectionAdapter;
use Archivr\Operation\DownloadOperation;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
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

        $testVaultConnection = new FlysystemConnectionAdapter(new Filesystem(new Local($testVault->getBasePath())));

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $operation = new DownloadOperation(basename($targetFilePath), $testBlobId);
        $operation->execute(dirname($targetFilePath) . DIRECTORY_SEPARATOR, $testVaultConnection);

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }

    public function testExecutionWithFilter()
    {
        $testBlobId = Uuid::uuid4();
        $testFileContent = 'Hello World!';

        $testVault = new TestVault();
        $testVault->fwrite($testBlobId, str_rot13($testFileContent));

        $testVaultConnection = new FlysystemConnectionAdapter(new Filesystem(new Local($testVault->getBasePath())));

        $targetFilePath = $this->getTemporaryPathGenerator()->getTemporaryFile();

        $operation = new DownloadOperation(basename($targetFilePath), $testBlobId, [
            'string.rot13' => []
        ]);
        $operation->execute(dirname($targetFilePath) . DIRECTORY_SEPARATOR, $testVaultConnection);

        $this->assertEquals($testFileContent, file_get_contents($targetFilePath));
    }
}
