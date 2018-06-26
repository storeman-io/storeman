<?php

namespace Storeman\Test\Index;

use Storeman\Exception;
use Storeman\Index\Index;
use PHPUnit\Framework\TestCase;
use Storeman\Test\TestVault;

class IndexTest extends TestCase
{
    public function testFileObjectAdditionAndRetrieval()
    {
        $testVault = new TestVault();
        $testVault->fwrite('file.ext', 'Hello World!');

        $index = $this->getNewIndex();
        $index->addObject($object = $testVault->getIndexObject('file.ext'));

        $this->assertEquals(1, count($index));
        $this->assertSame($object, $index->getObjectByPath('file.ext'));
    }

    public function testNullComparison()
    {
        $index = $this->getNewIndex();

        $this->assertFalse($index->equals(null));
    }

    public function testTopologicalInvalidAddition()
    {
        $this->expectException(Exception::class);

        $testVault = new TestVault();
        $testVault->mkdir('dir');
        $testVault->touch('dir/file.ext');

        $index = $this->getNewIndex();
        $index->addObject($testVault->getIndexObject('dir/file.ext'));
    }

    public function testTopologicalInvalidAddition2()
    {
        $this->expectException(Exception::class);

        $testVault = new TestVault();
        $testVault->touch('dir');

        $index = $this->getNewIndex();
        $index->addObject($testVault->getIndexObject('dir'));

        $testVault->remove('dir');
        $testVault->mkdir('dir');
        $testVault->touch('dir/file.ext');

        $index->addObject($testVault->getIndexObject('dir/file.ext'));
    }

    public function testTrivialIteration()
    {
        $testVault = new TestVault();
        $testVault->touch('a');

        $index = $this->getNewIndex();

        $this->assertEmpty(iterator_to_array($index->getIterator()));

        $index->addObject($objectA = $testVault->getIndexObject('a'));

        $array = array_values(iterator_to_array($index->getIterator()));

        $this->assertCount(1, $array);
        $this->assertSame($objectA, $array[0]);
    }

    public function testLexicographicOrderedIteration()
    {
        $testVault = new TestVault();
        $testVault->touch('a');
        $testVault->touch('b');

        $index = $this->getNewIndex();
        $index->addObject($objectA = $testVault->getIndexObject('a'));
        $index->addObject($objectB = $testVault->getIndexObject('b'));

        $array = array_values(iterator_to_array($index));

        $this->assertCount(2, $array);
        $this->assertSame($objectA, $array[0]);
        $this->assertSame($objectB, $array[1]);


        $index = $this->getNewIndex();
        $index->addObject($objectB = $testVault->getIndexObject('b'));
        $index->addObject($objectA = $testVault->getIndexObject('a'));

        $array = array_values(iterator_to_array($index));

        $this->assertCount(2, $array);
        $this->assertSame($objectA, $array[0]);
        $this->assertSame($objectB, $array[1]);
    }

    public function testTopologicalOrderedIteration()
    {
        $testVault = new TestVault();
        $testVault->mkdir('a');
        $testVault->touch('a/b');

        $index = $this->getNewIndex();
        $index->addObject($objectA = $testVault->getIndexObject('a'));
        $index->addObject($objectB = $testVault->getIndexObject('a/b'));

        $array = array_values(iterator_to_array($index));

        $this->assertCount(2, $array);
        $this->assertSame($objectA, $array[0]);
        $this->assertSame($objectB, $array[1]);
    }

    public function testCount()
    {
        $testVault = new TestVault();
        $testVault->touch('a');
        $testVault->mkdir('b');
        $testVault->touch('b/c');
        $testVault->mkdir('b/d');
        $testVault->touch('b/d/e');

        $index = $this->getNewIndex();
        $index->addObject($testVault->getIndexObject('a'));
        $index->addObject($testVault->getIndexObject('b'));
        $index->addObject($testVault->getIndexObject('b/c'));
        $index->addObject($testVault->getIndexObject('b/d'));
        $index->addObject($testVault->getIndexObject('b/d/e'));

        $this->assertEquals(5, $index->count());
    }

    public function testIsSubset()
    {
        $indexA = $this->getNewIndex();
        $indexB = $this->getNewIndex();

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $testVault = new TestVault();
        $testVault->fwrite('first.ext');
        $testVault->fwrite('second.ext');

        $firstObject = $testVault->getIndexObject('first.ext');
        $secondObject = $testVault->getIndexObject('second.ext');

        $this->assertTrue($indexA->isSubsetOf($indexB));

        $indexA->addObject($firstObject);

        $this->assertFalse($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));

        $indexB->addObject($firstObject);

        $this->assertTrue($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));

        $indexA->addObject($secondObject);

        $this->assertFalse($indexA->isSubsetOf($indexB));
        $this->assertTrue($indexB->isSubsetOf($indexA));
    }

    public function testComparison()
    {
        $indexA = $this->getNewIndex();
        $indexB = $this->getNewIndex();

        $this->assertTrue($this->areIndizesEqual($indexA, $indexA));
        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $testVault = new TestVault();
        $testVault->fwrite('first.ext');
        $testVault->fwrite('second.ext');

        $firstObject = $testVault->getIndexObject('first.ext');
        $secondObject = $testVault->getIndexObject('second.ext');

        $indexA->addObject($firstObject);

        $this->assertFalse($this->areIndizesEqual($indexA, $indexB));

        $indexB->addObject($firstObject);

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));

        $indexB->addObject($secondObject);

        $this->assertFalse($this->areIndizesEqual($indexA, $indexB));

        $indexA->addObject($secondObject);

        $this->assertTrue($this->areIndizesEqual($indexA, $indexB));
    }

    public function testTrivialMerge()
    {
        $testVault = new TestVault();
        $testVault->touch('a');
        $testVault->touch('b');

        $indexA = $this->getNewIndex();
        $indexA->addObject($objectA = $testVault->getIndexObject('a'));

        $indexB = $this->getNewIndex();
        $indexB->addObject($objectB = $testVault->getIndexObject('b'));

        $indexC = $indexA->merge($indexB);

        $this->assertCount(2, $indexC);
        $this->assertSame($objectA, $indexC->getObjectByPath($objectA->getRelativePath()));
        $this->assertSame($objectB, $indexC->getObjectByPath($objectB->getRelativePath()));
    }

    public function testOverrideMerge()
    {
        $testVault = new TestVault();
        $testVault->touch('a');

        $indexA = $this->getNewIndex();
        $indexA->addObject($objectA = $testVault->getIndexObject('a'));

        $indexB = $this->getNewIndex();
        $indexB->addObject($objectB = $testVault->getIndexObject('a'));

        $indexC = $indexA->merge($indexB);

        $this->assertCount(1, $indexC);
        $this->assertSame($objectB, $indexC->getObjectByPath($objectA->getRelativePath()));
    }

    public function testTreeMerge()
    {
        $testVaultA = new TestVault();
        $testVaultA->touch('file.ext');
        $testVaultA->mkdir('dir');
        $testVaultA->touch('dir/file.ext');
        $testVaultA->mkdir('dir/subdir');
        $testVaultA->touch('dir/subdir/file.ext');

        $indexA = $this->getNewIndex();
        $indexA->addObject($objectA = $testVaultA->getIndexObject('file.ext'));
        $indexA->addObject($testVaultA->getIndexObject('dir'));
        $indexA->addObject($objectB = $testVaultA->getIndexObject('dir/file.ext'));
        $indexA->addObject($testVaultA->getIndexObject('dir/subdir'));
        $indexA->addObject($testVaultA->getIndexObject('dir/subdir/file.ext'));

        $testVaultB = new TestVault();
        $testVaultB->mkdir('dir');
        $testVaultB->touch('dir/subdir');

        $indexB = $this->getNewIndex();
        $indexB->addObject($objectC = $testVaultB->getIndexObject('dir'));
        $indexB->addObject($objectD = $testVaultB->getIndexObject('dir/subdir'));

        $indexA->merge($indexB);

        $this->assertCount(4, $indexA);
        $this->assertSame($objectA, $indexA->getObjectByPath($objectA->getRelativePath()));
        $this->assertSame($objectB, $indexA->getObjectByPath($objectB->getRelativePath()));
        $this->assertSame($objectC, $indexA->getObjectByPath($objectC->getRelativePath()));
        $this->assertSame($objectD, $indexA->getObjectByPath($objectD->getRelativePath()));
    }

    public function testTrivialIndexDiffs()
    {
        $testVault = new TestVault();
        $testVault->touch('a');
        $testVault->touch('b');

        $indexA = $this->getNewIndex();
        $indexB = $this->getNewIndex();

        $this->assertCount(0, $indexA->getDifference($indexA));
        $this->assertCount(0, $indexA->getDifference($indexB));
        $this->assertCount(0, $indexB->getDifference($indexA));

        $indexA->addObject($objectA = $testVault->getIndexObject('a'));

        $this->assertCount(0, $indexA->getDifference($indexA));

        $diff = $indexA->getDifference($indexB);

        $this->assertCount(1, $diff);
        $this->assertSame($objectA, $diff->getObjectComparison('a')->getIndexObjectA());

        $diff = $indexB->getDifference($indexA);

        $this->assertCount(1, $diff);
        $this->assertSame($objectA, $diff->getObjectComparison('a')->getIndexObjectB());

        $indexB->addObject($objectA);

        $this->assertCount(0, $indexA->getDifference($indexB));
        $this->assertCount(0, $indexB->getDifference($indexA));

        $indexB->addObject($objectB = $testVault->getIndexObject('b'));

        $this->assertCount(1, $indexA->getDifference($indexB));
        $this->assertCount(1, $indexB->getDifference($indexA));

        $diff = $indexA->getDifference($indexB);

        $this->assertCount(1, $diff);
        $this->assertSame($objectB, $diff->getObjectComparison('b')->getIndexObjectB());

        $indexA->addObject($objectB);

        $this->assertCount(0, $indexA->getDifference($indexB));
        $this->assertCount(0, $indexB->getDifference($indexA));
    }

    public function testTreeDiff()
    {
        $testVault = new TestVault();
        $testVault->mkdir('a');
        $testVault->touch('a/b');
        $testVault->touch('a/c');
        $testVault->mkdir('a/d');
        $testVault->touch('a/d/e');
        $testVault->touch('a/d/f');

        $indexA = $this->getNewIndex();
        $indexA->addObject($objectA = $testVault->getIndexObject('a'));
        $indexA->addObject($objectB = $testVault->getIndexObject('a/b'));
        $indexA->addObject($objectC = $testVault->getIndexObject('a/c'));
        $indexA->addObject($objectD = $testVault->getIndexObject('a/d'));
        $indexA->addObject($objectE = $testVault->getIndexObject('a/d/e'));
        $indexA->addObject($objectF = $testVault->getIndexObject('a/d/f'));

        $existingPaths = array_keys(iterator_to_array($indexA));

        $indexB = $this->getNewIndex();

        $diff = $indexA->getDifference($indexB);

        $this->assertEquals($existingPaths, array_keys(iterator_to_array($diff)));

        $indexB->addObject($objectA);

        $diff = $indexA->getDifference($indexB);

        $this->assertEmpty(array_diff(
            array_diff($existingPaths, ['a']),
            array_keys(iterator_to_array($diff))
        ));
    }

    public function testTrivialIntersection()
    {
        $testVault = new TestVault();
        $testVault->touch('a');
        $testVault->touch('b');
        $testVault->touch('c');

        $objectA = $testVault->getIndexObject('a');
        $objectB = $testVault->getIndexObject('b');
        $objectC = $testVault->getIndexObject('c');

        $indexA = new Index();
        $indexA->addObject($objectA);
        $indexA->addObject($objectB);

        $indexB = new Index();
        $indexB->addObject($objectB);
        $indexB->addObject($objectC);

        $intersection = $indexA->getIntersection($indexB);

        $this->assertCount(1, $intersection);
        $this->assertTrue($intersection->hasObjectComparison('b'));
        $this->assertTrue($objectB->equals($intersection->getObjectComparison('b')->getIndexObjectA()));
        $this->assertTrue($objectB->equals($intersection->getObjectComparison('b')->getIndexObjectB()));
    }

    protected function areIndizesEqual(Index $indexA, Index $indexB)
    {
        return $indexA->equals($indexB) && $indexB->equals($indexA);
    }

    protected function getNewIndex(): Index
    {
        return new Index();
    }
}
