<?php

namespace Archivr\Test;

use Archivr\OperationResult;
use Archivr\OperationResultCollection;
use PHPUnit\Framework\TestCase;

class OperationResultCollectionTest extends TestCase
{
    public function testAddOperation()
    {
        $operationResultCollection = new OperationResultCollection();

        $this->assertEquals(0, $operationResultCollection->count());
        $this->assertEmpty($operationResultCollection->getOperationResults());
        $this->assertEmpty(iterator_to_array($operationResultCollection->getIterator()));

        /** @var OperationResult $operationResult */
        $operationResult = $this->createMock(OperationResult::class);

        $operationResultCollection->addOperationResult($operationResult);

        $this->assertEquals(1, $operationResultCollection->count());
        $this->assertEquals([$operationResult], $operationResultCollection->getOperationResults());
        $this->assertEquals([$operationResult], iterator_to_array($operationResultCollection->getIterator()));
    }

    public function testAppend()
    {
        /** @var OperationResult $firstOperationResult */
        $firstOperationResult = $this->createMock(OperationResult::class);

        /** @var OperationResult $secondOperationResult */
        $secondOperationResult = $this->createMock(OperationResult::class);

        $firstOperationResultCollection = new OperationResultCollection();
        $firstOperationResultCollection->addOperationResult($firstOperationResult);

        $secondOperationResultCollection = new OperationResultCollection();
        $secondOperationResultCollection->addOperationResult($secondOperationResult);

        $firstOperationResultCollection->append($secondOperationResultCollection);

        $this->assertEquals([$firstOperationResult, $secondOperationResult], $firstOperationResultCollection->getOperationResults());
        $this->assertEquals([$firstOperationResult, $secondOperationResult], iterator_to_array($firstOperationResultCollection->getIterator()));
        $this->assertEquals([$secondOperationResult], $secondOperationResultCollection->getOperationResults());
        $this->assertEquals([$secondOperationResult], iterator_to_array($secondOperationResultCollection->getIterator()));
    }
}
