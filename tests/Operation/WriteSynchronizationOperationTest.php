<?php

namespace Storeman\Test\Operation;

use PHPUnit\Framework\TestCase;
use Storeman\Operation\WriteSynchronizationOperation;
use Storeman\Synchronization;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\VaultLayout\VaultLayoutInterface;

class WriteSynchronizationOperationTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function test()
    {
        $vaultLayout = $this->createMock(VaultLayoutInterface::class);
        $vaultLayout->expects($this->once())->method('writeSynchronization');

        $operation = new WriteSynchronizationOperation($this->createMock(Synchronization::class));
        $operation->execute($this->getTemporaryPathGenerator()->getTemporaryDirectory(), $vaultLayout);
    }
}
