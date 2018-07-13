<?php

namespace Storeman\Test;

use Storeman\Operation\OperationInterface;
use Storeman\OperationList;
use PHPUnit\Framework\TestCase;
use Storeman\OperationListItem;

class OperationListTest extends TestCase
{
    use ConfiguredMockProviderTrait;

    public function testAddOperation()
    {
        $operationList = new OperationList();

        $this->assertEquals(0, $operationList->count());
        $this->assertEmpty(iterator_to_array($operationList->getIterator()));

        /** @var OperationInterface $operation */
        $operation = $this->createMock(OperationInterface::class);

        $operationListItem = new OperationListItem($operation);
        $operationList->add($operationListItem);

        $this->assertEquals(1, $operationList->count());
        $this->assertEquals([$operationListItem], iterator_to_array($operationList->getIterator()));
    }

    public function testAppend()
    {
        /** @var OperationInterface $firstOperation */
        $firstOperation = $this->createMock(OperationInterface::class);

        /** @var OperationInterface $secondOperation */
        $secondOperation = $this->createMock(OperationInterface::class);

        $first = new OperationList();
        $firstOperationListItem = new OperationListItem($firstOperation);
        $first->add($firstOperationListItem);

        $second = new OperationList();
        $secondOperationListItem = new OperationListItem($secondOperation);
        $second->add($secondOperationListItem);

        $first->append($second);

        $this->assertEquals([$firstOperationListItem, $secondOperationListItem], iterator_to_array($first->getIterator()));
        $this->assertEquals([$secondOperationListItem], iterator_to_array($second->getIterator()));
    }

    public function testToArray()
    {
        $list = new OperationList();
        $list->add($this->getOperationListItemMock());
        $list->add($this->getOperationListItemMock());

        $this->assertEquals(iterator_to_array($list->getIterator()), $list->toArray());
    }
}
