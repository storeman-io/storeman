<?php

namespace Archivr\Test;

use Archivr\Operation\OperationInterface;
use Archivr\OperationCollection;
use PHPUnit\Framework\TestCase;

class OperationCollectionTest extends TestCase
{
    public function testAddOperation()
    {
        $operationCollection = new OperationCollection();

        $this->assertEquals(0, $operationCollection->count());
        $this->assertEmpty(iterator_to_array($operationCollection->getIterator()));

        /** @var OperationInterface $operation */
        $operation = $this->createMock(OperationInterface::class);

        $operationCollection->addOperation($operation);

        $this->assertEquals(1, $operationCollection->count());
        $this->assertEquals([$operation], iterator_to_array($operationCollection->getIterator()));
    }

    public function testAppend()
    {
        /** @var OperationInterface $firstOperation */
        $firstOperation = $this->createMock(OperationInterface::class);

        /** @var OperationInterface $secondOperation */
        $secondOperation = $this->createMock(OperationInterface::class);

        $firstOperationCollection = new OperationCollection();
        $firstOperationCollection->addOperation($firstOperation);

        $secondOperationCollection = new OperationCollection();
        $secondOperationCollection->addOperation($secondOperation);

        $firstOperationCollection->append($secondOperationCollection);

        $this->assertEquals([$firstOperation, $secondOperation], iterator_to_array($firstOperationCollection->getIterator()));
        $this->assertEquals([$secondOperation], iterator_to_array($secondOperationCollection->getIterator()));
    }
}
