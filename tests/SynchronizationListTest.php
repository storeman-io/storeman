<?php

namespace Storeman\Test;

use Storeman\Synchronization;
use Storeman\SynchronizationList;
use PHPUnit\Framework\TestCase;

class SynchronizationListTest extends TestCase
{
    public function testCount()
    {
        $list = new SynchronizationList();
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => 1]));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => 2]));

        $this->assertCount(2, $list);
    }

    public function testGetSynchronizationByTime()
    {
        $list = new SynchronizationList();
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => 1, 'getTime' => new \DateTime('-2 hours')]));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => 2, 'getTime' => new \DateTime('-1 hours')]));

        $this->assertNull($list->getSynchronizationByTime(new \DateTime('-3 hours')));
        $this->assertEquals(1, $list->getSynchronizationByTime(new \DateTime('-90 minutes'))->getRevision());
        $this->assertEquals(2, $list->getSynchronizationByTime(new \DateTime())->getRevision());
    }

    public function testGetSynchronizationsByIdentity()
    {
        $rev = 1;

        $list = new SynchronizationList();
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => $rev++, 'getIdentity' => 'a']));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => $rev++, 'getIdentity' => 'b']));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => $rev++, 'getIdentity' => 'c']));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => $rev++, 'getIdentity' => 'b']));
        $list->addSynchronization($this->createConfiguredMock(Synchronization::class, ['getRevision' => $rev++, 'getIdentity' => 'a']));

        $listA = $list->getSynchronizationsByIdentity('a');
        $this->assertCount(2, $listA);
        $this->assertEquals([1, 5], $listA->getRevisions());

        $listB = $list->getSynchronizationsByIdentity('b');
        $this->assertCount(2, $listB);
        $this->assertEquals([2, 4], $listB->getRevisions());

        $listC = $list->getSynchronizationsByIdentity('c');
        $this->assertCount(1, $listC);
        $this->assertEquals([3], $listC->getRevisions());
    }
}
