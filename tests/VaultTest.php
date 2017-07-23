<?php

namespace Archivr\Test;

use Archivr\Connection\DummyConnection;
use Archivr\Index;
use Archivr\IndexObject;
use Archivr\Vault;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class VaultTest extends TestCase
{
    use TestVaultGeneratorProviderTrait;

    public function testBuildLocalIndex()
    {
        $testVault = $this->getTestVaultGenerator()->generate();
        $vault = new Vault(new DummyConnection(), $testVault->getBasePath());

        $localIndex = $vault->buildLocalIndex();

        $this->assertInstanceOf(Index::class, $localIndex);

        foreach ($testVault as $testVaultObject)
        {
            /** @var SplFileInfo $testVaultObject */

            $indexObject = $localIndex->getObjectByPath($testVaultObject->getRelativePathname());

            $this->assertInstanceOf(IndexObject::class, $indexObject);
            $this->assertEquals($testVaultObject->isFile(), $indexObject->isFile());
            $this->assertEquals($testVaultObject->isDir(), $indexObject->isDirectory());
            $this->assertEquals($testVaultObject->isLink(), $indexObject->isLink());
            $this->assertEquals($testVaultObject->getMTime(), $indexObject->getMtime());
            $this->assertEquals($testVaultObject->getCTime(), $indexObject->getCtime());
            $this->assertEquals($testVaultObject->getPerms(), $indexObject->getMode());
        }
    }
}