<?php

namespace Storeman\IndexMerger;

use Storeman\Config\Configuration;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Hash\HashProvider;
use Storeman\Index\Comparison\IndexObjectComparison;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;

class StandardIndexMerger implements IndexMergerInterface
{
    public const VERIFY_CONTENT = 1;


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
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, ?Index $lastLocalIndex, int $options = 0): Index
    {
        $mergedIndex = new Index();
        $lastLocalIndex = $lastLocalIndex ?: new Index();

        $diff = $localIndex->getDifference($remoteIndex, IndexObject::CMP_IGNORE_BLOBID);

        foreach ($diff as $cmp)
        {
            /** @var IndexObjectComparison $cmp */

            $localObject = $localIndex->getObjectByPath($cmp->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($cmp->getRelativePath());
            $remoteObject = $remoteIndex->getObjectByPath($cmp->getRelativePath());

            $localObjectModified = $this->isLocalObjectModified($localObject, $lastLocalObject, $options);
            $remoteObjectModified = $this->isRemoteObjectModified($remoteObject, $lastLocalObject);

            if ($localObjectModified && $remoteObjectModified)
            {
                $mergedIndex->addObject($this->resolveConflict($conflictHandler, $remoteObject, $localObject, $lastLocalObject));
            }
            elseif ($localObjectModified && $localObject !== null)
            {
                $mergedIndex->addObject($localObject);
            }
            elseif ($remoteObjectModified && $remoteObject !== null)
            {
                $mergedIndex->addObject($remoteObject);
            }
        }

        foreach ($localIndex->getIntersection($remoteIndex) as $cmp)
        {
            /** @var IndexObjectComparison $cmp */

            $mergedIndex->addObject($cmp->getIndexObjectA());
        }

        return $mergedIndex;
    }

    protected function isLocalObjectModified(?IndexObject $localObject, ?IndexObject $lastLocalObject, int $options): bool
    {
        if (!$lastLocalObject)
        {
            return $localObject !== null;
        }

        $localObjectModified = !$lastLocalObject->equals($localObject, IndexObject::CMP_IGNORE_BLOBID);

        // eventually verify file content
        if (!$localObjectModified && $localObject->isFile() && $options & static::VERIFY_CONTENT)
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
                        $localObjectModified = true;
                    }
                }
            }
        }

        return $localObjectModified;
    }

    protected function isRemoteObjectModified(?IndexObject $remoteObject, ?IndexObject $lastLocalObject): bool
    {
        if ($lastLocalObject)
        {
            return !$lastLocalObject->equals($remoteObject, IndexObject::CMP_IGNORE_BLOBID);
        }
        else
        {
            return $remoteObject !== null;
        }
    }

    protected function resolveConflict(ConflictHandlerInterface $conflictHandler, IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): IndexObject
    {
        assert(($localObject === null) || ($localObject->getRelativePath() === $remoteObject->getRelativePath()));
        assert(($lastLocalObject === null) || ($lastLocalObject->getRelativePath() === $remoteObject->getRelativePath()));

        $solution = $conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalObject);

        $return = null;
        switch ($solution)
        {
            case ConflictHandlerInterface::USE_LOCAL:

                if ($localObject)
                {
                    $return = $localObject;
                }

                break;

            case ConflictHandlerInterface::USE_REMOTE:

                if ($remoteObject)
                {
                    $return = $remoteObject;
                }

                break;

            default:

                throw new \LogicException();
        }

        return $return;
    }
}
