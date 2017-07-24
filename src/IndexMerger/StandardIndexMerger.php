<?php

namespace Archivr\IndexMerger;

use Archivr\Index;
use Archivr\IndexObject;

class StandardIndexMerger implements IndexMergerInterface
{
    public function merge(Index $localIndex, Index $lastLocalIndex = null, Index $remoteIndex = null): Index
    {
        $mergedIndex = new Index();

        // build new index from local index
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($localObject->getRelativePath()) : null;

            $localObjectModified = $lastLocalIndex ? ($localObject->getMtime() > $lastLocalIndex->getCreated()->getTimestamp()) : true;

            if ($remoteObject === null)
            {
                if ($localObjectModified)
                {
                    $mergedIndex->addObject($localObject);
                }
                elseif ($remoteIndex === null)
                {
                    $mergedIndex->addObject($localObject);
                }
            }
            else
            {
                // todo: merge mode by ctime

                $remoteObjectModified = $remoteObject->getMtime() > $lastLocalIndex->getCreated()->getTimestamp();

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
                    // todo: properly handle collision
                    throw new \RuntimeException("Collision at path {$localObject->getRelativePath()}");
                }
            }
        }

        if ($remoteIndex !== null)
        {
            // add remote index content
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
        }

        return $mergedIndex;
    }
}