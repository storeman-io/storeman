<?php

namespace Storeman\Test\OperationListBuilder;

use Storeman\Index\Index;
use Storeman\Operation\OperationInterface;
use Storeman\Operation\TouchOperation;
use Storeman\Operation\UnlinkOperation;
use Storeman\OperationListBuilder\StandardOperationListBuilder;
use Storeman\Test\TemporaryPathGeneratorProviderTrait;
use Storeman\Test\TestVault;
use PHPUnit\Framework\TestCase;

class StandardOperationListBuilderTest extends TestCase
{
    use TemporaryPathGeneratorProviderTrait;

    public function testCorrectDirectoryTreeTouchOrder()
    {
        $testVault = new TestVault();
        $testVault->mkdir('a');
        $testVault->mkdir('a/b');
        $testVault->mkdir('a/b/c');
        $testVault->touch('a/b/c', random_int(0, time()));
        $testVault->touch('a/b', random_int(0, time()));
        $testVault->touch('a', random_int(0, time()));

        $builder = new StandardOperationListBuilder();

        $localIndex = new Index();
        $mergedIndex = new Index();
        $mergedIndex->addObject($testVault->getIndexObject('a'));
        $mergedIndex->addObject($testVault->getIndexObject('a/b'));
        $mergedIndex->addObject($testVault->getIndexObject('a/b/c'));

        $operationList = $builder->buildOperationList($mergedIndex, $localIndex);

        /** @var TouchOperation[] $touchOperations */
        $touchOperations = array_values(array_filter(iterator_to_array($operationList), function(OperationInterface $operation) {

            return $operation instanceof TouchOperation;
        }));

        $this->assertCount(3, $touchOperations);

        $this->assertEquals('a/b/c', $touchOperations[0]->getRelativePath());
        $this->assertEquals('a/b', $touchOperations[1]->getRelativePath());
        $this->assertEquals('a', $touchOperations[2]->getRelativePath());
    }

    public function testCorrectDirectoryDeletionOrder()
    {
        $testVault = new TestVault();
        $testVault->mkdir('a/b/c');
        $testVault->touch('a/b/c/d.ext');

        $mergedIndex = new Index();
        $mergedIndex->addObject($testVault->getIndexObject('a'));
        $localIndex = $testVault->getIndex();

        $builder = new StandardOperationListBuilder();
        $operationList = $builder->buildOperationList($mergedIndex, $localIndex);

        /** @var UnlinkOperation[] $unlinkOperations */
        $unlinkOperations = array_values(array_filter(iterator_to_array($operationList), function(OperationInterface $operation) {

            return $operation instanceof UnlinkOperation;
        }));

        $this->assertCount(3, $unlinkOperations);
        $this->assertEquals('a/b/c/d.ext', $unlinkOperations[0]->getRelativePath());
        $this->assertEquals('a/b/c', $unlinkOperations[1]->getRelativePath());
        $this->assertEquals('a/b', $unlinkOperations[2]->getRelativePath());
    }
}
