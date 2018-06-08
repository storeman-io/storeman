<?php

namespace Storeman\Test;

use Storeman\OperationResult;
use Storeman\OperationResultList;
use PHPUnit\Framework\TestCase;

class OperationResultListTest extends TestCase
{
    public function testAddOperation()
    {
        $list = new OperationResultList();

        $this->assertEquals(0, $list->count());
        $this->assertEmpty(iterator_to_array($list->getIterator()));

        /** @var OperationResult $operationResult */
        $operationResult = $this->createMock(OperationResult::class);

        $list->addOperationResult($operationResult);

        $this->assertEquals(1, $list->count());
        $this->assertEquals([$operationResult], iterator_to_array($list->getIterator()));
    }

    public function testAppend()
    {
        /** @var OperationResult $firstOperationResult */
        $firstOperationResult = $this->createMock(OperationResult::class);

        /** @var OperationResult $secondOperationResult */
        $secondOperationResult = $this->createMock(OperationResult::class);

        $first = new OperationResultList();
        $first->addOperationResult($firstOperationResult);

        $second = new OperationResultList();
        $second->addOperationResult($secondOperationResult);

        $first->append($second);

        $this->assertEquals([$firstOperationResult, $secondOperationResult], iterator_to_array($first->getIterator()));
        $this->assertEquals([$secondOperationResult], iterator_to_array($second->getIterator()));
    }
}
