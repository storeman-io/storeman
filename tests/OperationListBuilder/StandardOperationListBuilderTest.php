<?php

namespace Archivr\Test\OperationListBuilder;

use Archivr\Index;
use Archivr\IndexObject;
use Archivr\Operation\OperationInterface;
use Archivr\Operation\TouchOperation;
use Archivr\OperationListBuilder\StandardOperationListBuilder;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
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
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a'));
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a/b'));
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a/b/c'));

        $operationList = $builder->buildOperationList($mergedIndex, $localIndex, $mergedIndex);

        /** @var TouchOperation[] $touchOperations */
        $touchOperations = array_values(array_filter(iterator_to_array($operationList), function(OperationInterface $operation) {

            return $operation instanceof TouchOperation;
        }));

        $this->assertCount(3, $touchOperations);

        $this->assertEquals('a/b/c', $touchOperations[0]->getRelativePath());
        $this->assertEquals('a/b', $touchOperations[1]->getRelativePath());
        $this->assertEquals('a', $touchOperations[2]->getRelativePath());
    }
}