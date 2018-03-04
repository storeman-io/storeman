<?php

namespace Archivr\Test\OperationCollectionBuilder;

use Archivr\ConnectionAdapter\DummyConnectionAdapter;
use Archivr\Index;
use Archivr\IndexObject;
use Archivr\Operation\OperationInterface;
use Archivr\Operation\TouchOperation;
use Archivr\OperationCollectionBuilder\StandardOperationCollectionBuilder;
use Archivr\Test\TemporaryPathGeneratorProviderTrait;
use Archivr\Test\TestVault;
use Archivr\Vault;
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

        $builder = $this->getBuilder();

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

        $basePath = $builder->getVault()->getLocalPath();
        $this->assertEquals($basePath . 'a/b/c', $touchOperations[0]->getAbsolutePath());
        $this->assertEquals($basePath . 'a/b', $touchOperations[1]->getAbsolutePath());
        $this->assertEquals($basePath . 'a', $touchOperations[2]->getAbsolutePath());
    }

    protected function getBuilder(): StandardOperationCollectionBuilder
    {
        $vault = new Vault('test', $this->getTemporaryPathGenerator()->getTemporaryDirectory(), new DummyConnectionAdapter());

        return new StandardOperationCollectionBuilder($vault);
    }
}
