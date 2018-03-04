<?php

namespace Archivr\Test;

use Archivr\Synchronization;
use Archivr\SynchronizationList;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SynchronizationListTest extends TestCase
{
    public function testCount()
    {
        $list = new SynchronizationList();
        $list->addSynchronization(new Synchronization(1, Uuid::uuid4()->toString(), new \DateTime()));
        $list->addSynchronization(new Synchronization(2, Uuid::uuid4()->toString(), new \DateTime()));

        $this->assertCount(2, $list);
    }

    public function testGetSynchronizationByTime()
    {
        $list = new SynchronizationList();
        $list->addSynchronization(new Synchronization(1, Uuid::uuid4()->toString(), new \DateTime('-2 hours')));
        $list->addSynchronization(new Synchronization(2, Uuid::uuid4()->toString(), new \DateTime('-1 hours')));

        $this->assertNull($list->getSynchronizationByTime(new \DateTime('-3 hours')));
        $this->assertEquals(1, $list->getSynchronizationByTime(new \DateTime('-90 minutes'))->getRevision());
        $this->assertEquals(2, $list->getSynchronizationByTime(new \DateTime())->getRevision());
    }

    public function testGetSynchronizationsByIdentity()
    {
        $rev = 1;

        $list = new SynchronizationList();
        $list->addSynchronization(new Synchronization($rev++, Uuid::uuid4()->toString(), new \DateTime(), 'a'));
        $list->addSynchronization(new Synchronization($rev++, Uuid::uuid4()->toString(), new \DateTime(), 'b'));
        $list->addSynchronization(new Synchronization($rev++, Uuid::uuid4()->toString(), new \DateTime(), 'c'));
        $list->addSynchronization(new Synchronization($rev++, Uuid::uuid4()->toString(), new \DateTime(), 'b'));
        $list->addSynchronization(new Synchronization($rev++, Uuid::uuid4()->toString(), new \DateTime(), 'a'));

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
