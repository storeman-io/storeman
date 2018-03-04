<?php

namespace Archivr\Test;

use Archivr\Operation\OperationInterface;
use Archivr\OperationList;
use PHPUnit\Framework\TestCase;

class OperationListTest extends TestCase
{
    public function testAddOperation()
    {
        $operationList = new OperationList();

        $this->assertEquals(0, $operationList->count());
        $this->assertEmpty(iterator_to_array($operationList->getIterator()));

        /** @var OperationInterface $operation */
        $operation = $this->createMock(OperationInterface::class);

        $operationList->addOperation($operation);

        $this->assertEquals(1, $operationList->count());
        $this->assertEquals([$operation], iterator_to_array($operationList->getIterator()));
    }

    public function testAppend()
    {
        /** @var OperationInterface $firstOperation */
        $firstOperation = $this->createMock(OperationInterface::class);

        /** @var OperationInterface $secondOperation */
        $secondOperation = $this->createMock(OperationInterface::class);

        $first = new OperationList();
        $first->addOperation($firstOperation);

        $second = new OperationList();
        $second->addOperation($secondOperation);

        $first->append($second);

        $this->assertEquals([$firstOperation, $secondOperation], iterator_to_array($first->getIterator()));
        $this->assertEquals([$secondOperation], iterator_to_array($second->getIterator()));
    }
}
