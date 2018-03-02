<?php

namespace Archivr\IndexMerger;

use Archivr\ConflictHandler\ConflictHandlerInterface;
use Archivr\ConflictHandler\PanickingConflictHandler;
use Archivr\Index;
use Archivr\IndexObject;

class StandardIndexMerger implements IndexMergerInterface
{
    /**
     * @var ConflictHandlerInterface
     */
    protected $conflictHandler;

    public function setConflictHandler(ConflictHandlerInterface $conflictHandler = null): IndexMergerInterface
    {
        $this->conflictHandler = $conflictHandler;

        return $this;
    }

    public function getConflictHandler(): ConflictHandlerInterface
    {
        if ($this->conflictHandler === null)
        {
            $this->setConflictHandler(new PanickingConflictHandler());
        }

        return $this->conflictHandler;
    }

    public function merge(Index $remoteIndex, Index $localIndex, Index $lastLocalIndex = null): Index
    {
        $mergedIndex = new Index();
        $conflictHandler = $this->getConflictHandler();

        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            $remoteObject = $remoteIndex->getObjectByPath($localObject->getRelativePath());

            $localObjectModified = $lastLocalIndex ? ($localObject->getMtime() > $lastLocalIndex->getCreated()->getTimestamp()) : true;

            if ($remoteObject === null)
            {
                if ($localObjectModified)
                {
                    $mergedIndex->addObject($localObject);
                }
            }
            else
            {
                $remoteObjectModified = $lastLocalIndex ? ($remoteObject->getMtime() > $lastLocalIndex->getCreated()->getTimestamp()) : false;

                if (!$localObjectModified)
                {
                    $mergedIndex->addObject($remoteObject);
                }

                elseif (!$remoteObjectModified)
                {
                    $mergedIndex->addObject($localObject);
                }

                else
                {
                    $conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalIndex ? $lastLocalIndex->getObjectByPath($localObject->getRelativePath()) : null);
                }
            }
        }

        foreach ($remoteIndex as $remoteObject)
        {
            /** @var IndexObject $remoteObject */

            $localObject = $localIndex->getObjectByPath($remoteObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex ? $lastLocalIndex->getObjectByPath($remoteObject->getRelativePath()) : null;

            if ($localObject === null)
            {
                if ($lastLocalObject === null)
                {
                    $mergedIndex->addObject($remoteObject);
                }
            }
        }

        return $mergedIndex;
    }
}
