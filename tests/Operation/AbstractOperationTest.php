<?php

namespace Storeman\Test\Operation;

use PHPUnit\Framework\TestCase;
use Storeman\FileReader;
use Storeman\VaultLayout\VaultLayoutInterface;

abstract class AbstractOperationTest extends TestCase
{
    protected function getFileReaderMock(): FileReader
    {
        /** @var FileReader $fileReader */
        $fileReader = $this->createMock(FileReader::class);

        return $fileReader;
    }

    protected function getVaultLayoutMock(): VaultLayoutInterface
    {
        /** @var VaultLayoutInterface $vaultLayout */
        $vaultLayout = $this->createMock(VaultLayoutInterface::class);

        return $vaultLayout;
    }
}
