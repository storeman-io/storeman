<?php

namespace Archivr\Test\OperationCollectionBuilder;

use Archivr\Index;
use Archivr\IndexObject;
use Archivr\Operation\OperationInterface;
use Archivr\Operation\TouchOperation;
use Archivr\OperationCollectionBuilder\StandardOperationCollectionBuilder;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use PHPUnit\Framework\TestCase;

class StandardOperationCollectionBuilderTest extends TestCase
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

        $builder = new StandardOperationCollectionBuilder();

        $localIndex = new Index();
        $mergedIndex = new Index();
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a'));
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a/b'));
        $mergedIndex->addObject(IndexObject::fromPath($testVault->getBasePath(), 'a/b/c'));

        $operationCollection = $builder->buildOperationCollection($mergedIndex, $localIndex, $mergedIndex);

        /** @var TouchOperation[] $touchOperations */
        $touchOperations = array_values(array_filter(iterator_to_array($operationCollection), function(OperationInterface $operation) {

            return $operation instanceof TouchOperation;
        }));

        $this->assertCount(3, $touchOperations);

        $this->assertEquals('a/b/c', $touchOperations[0]->getRelativePath());
        $this->assertEquals('a/b', $touchOperations[1]->getRelativePath());
        $this->assertEquals('a', $touchOperations[2]->getRelativePath());
    }
}
