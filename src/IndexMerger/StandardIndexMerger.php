<?php

namespace Storeman\IndexMerger;

use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;

class StandardIndexMerger implements IndexMergerInterface
{
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, Index $lastLocalIndex = null): Index
    {
        $mergedIndex = new Index();

        if ($lastLocalIndex === null)
        {
            // lets make our life easier
            $lastLocalIndex = new Index();
        }

        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            $remoteObject = $remoteIndex->getObjectByPath($localObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($localObject->getRelativePath());


            // compare existing to known object
            if ($lastLocalObject)
            {
                $localObjectModified = false;
                $localObjectModified = $localObjectModified || ($localObject->getType() !== $lastLocalObject->getType());
                $localObjectModified = $localObjectModified || ($localObject->getMtime() !== $lastLocalObject->getMtime());
                $localObjectModified = $localObjectModified || ($localObject->getCtime() !== $lastLocalObject->getCtime());
                $localObjectModified = $localObjectModified || ($localObject->getMode() !== $lastLocalObject->getMode());
                $localObjectModified = $localObjectModified || ($localObject->getSize() !== $lastLocalObject->getSize());
                $localObjectModified = $localObjectModified || ($localObject->getLinkTarget() !== $lastLocalObject->getLinkTarget());

                // remote object has been modified if it does not equal the object on its last synchronization
                $remoteObjectModified = !$lastLocalObject->equals($remoteObject);
            }

            // object has been created
            else
            {
                $localObjectModified = true;

                // object has been created since last synchronization
                $remoteObjectModified = $remoteObject !== null;
            }


            // conflict if both the local and the remote object has been changed
            if ($localObjectModified && $remoteObjectModified)
            {
                $this->conflict($conflictHandler, $mergedIndex, $remoteObject, $localObject, $lastLocalObject);
            }

            // add the remote object if only it has been modified
            elseif ($remoteObjectModified)
            {
                $mergedIndex->addObject($remoteObject);
            }

            // add the local object otherwise if only it or none has been modified
            else
            {
                $mergedIndex->addObject($localObject);
            }
        }

        foreach ($remoteIndex as $remoteObject)
        {
            /** @var IndexObject $remoteObject */

            $localObject = $localIndex->getObjectByPath($remoteObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($remoteObject->getRelativePath());

            // only consider objects not existing locally as those are already considered by the first loop
            if (!$localObject)
            {
                if ($lastLocalObject)
                {
                    // local object has been deleted
                    $localObjectModified = true;

                    // compare remote object to object state at last sync
                    $remoteObjectModified = !$lastLocalObject->equals($remoteObject);
                }

                else
                {
                    // object already didn't exist locally at the last sync
                    $localObjectModified = false;

                    // another client added the remote object
                    $remoteObjectModified = true;
                }


                // conflict if both the local and the remote object has been changed
                if ($localObjectModified && $remoteObjectModified)
                {
                    $this->conflict($conflictHandler, $mergedIndex, $remoteObject, $localObject, $lastLocalObject);
                }

                // another client added the remote object
                elseif (!$lastLocalObject)
                {
                    $mergedIndex->addObject($remoteObject);
                }
            }
        }

        return $mergedIndex;
    }

    protected function conflict(ConflictHandlerInterface $conflictHandler, Index $mergedIndex, IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): void
    {
        $solution = $conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalObject);

        switch ($solution)
        {
            case ConflictHandlerInterface::USE_LOCAL:

                if ($localObject)
                {
                    $mergedIndex->addObject($localObject);
                }

                break;

            case ConflictHandlerInterface::USE_REMOTE:

                if ($remoteObject)
                {
                    $mergedIndex->addObject($remoteObject);
                }

                break;

            default:

                throw new \LogicException();
        }
    }
}
