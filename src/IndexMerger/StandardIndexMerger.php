<?php

namespace Storeman\IndexMerger;

use Storeman\Config\Configuration;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Hash\HashProvider;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;

class StandardIndexMerger implements IndexMergerInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var HashProvider
     */
    protected $hashProvider;

    public function __construct(Configuration $configuration, HashProvider $hashProvider)
    {
        $this->configuration = $configuration;
        $this->hashProvider = $hashProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, ?Index $lastLocalIndex): Index
    {
        $mergedIndex = new Index();
        $lastLocalIndex = $lastLocalIndex ?: new Index();

        $this->inspectLocalIndex($mergedIndex, $conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);
        $this->inspectRemoteIndex($mergedIndex, $conflictHandler, $remoteIndex, $localIndex, $lastLocalIndex);

        return $mergedIndex;
    }

    protected function inspectLocalIndex(Index $mergedIndex, ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, Index $lastLocalIndex): void
    {
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            $remoteObject = $remoteIndex->getObjectByPath($localObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($localObject->getRelativePath());


            // compare existing to known object
            if ($lastLocalObject)
            {
                $localObjectModified = $this->isLocalObjectModified($localObject, $lastLocalObject);
                $remoteObjectModified = $this->isRemoteObjectModified($remoteObject, $lastLocalObject);
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
    }

    protected function inspectRemoteIndex(Index $mergedIndex, ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, Index $lastLocalIndex): void
    {
        foreach ($remoteIndex as $remoteObject)
        {
            /** @var IndexObject $remoteObject */

            // only consider objects not existing locally as those are already considered
            if ($localIndex->getObjectByPath($remoteObject->getRelativePath()))
            {
                continue;
            }


            $lastLocalObject = $lastLocalIndex->getObjectByPath($remoteObject->getRelativePath());

            // local object has been deleted
            if ($lastLocalObject)
            {
                $localObjectModified = true;

                // compare remote object to object state at last sync
                $remoteObjectModified = $this->isRemoteObjectModified($remoteObject, $lastLocalObject);
            }

            // another client added the remote object
            else
            {
                $remoteObjectModified = true;

                // object already didn't exist locally
                $localObjectModified = false;
            }


            // conflict if both the local and the remote object has been changed
            if ($localObjectModified && $remoteObjectModified)
            {
                $this->conflict($conflictHandler, $mergedIndex, $remoteObject, null, $lastLocalObject);
            }

            // another client added the remote object
            elseif (!$lastLocalObject)
            {
                $mergedIndex->addObject($remoteObject);
            }
        }
    }

    protected function isLocalObjectModified(IndexObject $localObject, IndexObject $lastLocalObject): bool
    {
        $localObjectModified = false;
        $localObjectModified = $localObjectModified || ($localObject->getType() !== $lastLocalObject->getType());
        $localObjectModified = $localObjectModified || ($localObject->getMtime() !== $lastLocalObject->getMtime());
        $localObjectModified = $localObjectModified || ($localObject->getCtime() !== $lastLocalObject->getCtime());
        $localObjectModified = $localObjectModified || ($localObject->getMode() !== $lastLocalObject->getMode());
        $localObjectModified = $localObjectModified || ($localObject->getSize() !== $lastLocalObject->getSize());
        $localObjectModified = $localObjectModified || ($localObject->getInode() !== $lastLocalObject->getInode());
        $localObjectModified = $localObjectModified || ($localObject->getLinkTarget() !== $lastLocalObject->getLinkTarget());

        if (!$localObjectModified && $localObject->isFile())
        {
            $existingHashes = iterator_to_array($lastLocalObject->getHashes());
            $configuredAlgorithms = $this->configuration->getFileChecksums();

            if (!empty($comparableAlgorithms = array_intersect($configuredAlgorithms, array_keys($existingHashes))))
            {
                $this->hashProvider->loadHashes($localObject, $comparableAlgorithms);

                foreach ($comparableAlgorithms as $algorithm)
                {
                    if ($this->hashProvider->getHash($localObject, $algorithm) !== $existingHashes[$algorithm])
                    {
                        $localObjectModified = false;
                    }
                }
            }
        }

        return $localObjectModified;
    }

    protected function isRemoteObjectModified(IndexObject $remoteObject, IndexObject $lastLocalObject): bool
    {
        // remote object has been modified if it does not equal the object on its last synchronization
        return !$lastLocalObject->equals($remoteObject);
    }

    protected function conflict(ConflictHandlerInterface $conflictHandler, Index $mergedIndex, IndexObject $remoteObject, IndexObject $localObject = null, IndexObject $lastLocalObject = null): void
    {
        assert($localObject->getRelativePath() === $remoteObject->getRelativePath());
        assert($localObject->getRelativePath() === $lastLocalObject->getRelativePath());

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
