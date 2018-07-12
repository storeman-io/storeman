<?php

namespace Storeman\IndexMerger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Storeman\Config\Configuration;
use Storeman\ConflictHandler\ConflictHandlerInterface;
use Storeman\Hash\HashProvider;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;

class StandardIndexMerger implements IndexMergerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const VERIFY_CONTENT = 2;

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
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function merge(ConflictHandlerInterface $conflictHandler, Index $remoteIndex, Index $localIndex, ?Index $lastLocalIndex, int $options = 0): Index
    {
        $this->logger->info(sprintf("Merging indices using %s (Options: %s)", static::class, static::getOptionsDebugString($options)));

        $mergedIndex = new Index();
        $lastLocalIndex = $lastLocalIndex ?: new Index();

        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            $remoteObject = $remoteIndex->getObjectByPath($localObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($localObject->getRelativePath());

            if ($mergedObject = $this->mergeObject1($conflictHandler, $remoteObject, $localObject, $lastLocalObject, $options))
            {
                $mergedIndex->addObject($mergedObject);
            }
        }

        foreach ($remoteIndex as $remoteObject)
        {
            /** @var IndexObject $remoteObject */

            $localObject = $localIndex->getObjectByPath($remoteObject->getRelativePath());
            $lastLocalObject = $lastLocalIndex->getObjectByPath($remoteObject->getRelativePath());

            if ($localObject !== null)
            {
                // already taken care of in local index iteration
                continue;
            }

            if ($mergedObject = $this->mergeObject1($conflictHandler, $remoteObject, $localObject, $lastLocalObject, $options))
            {
                $mergedIndex->addObject($mergedObject);
            }
        }

        if ($options & static::INJECT_BLOBID)
        {
            $this->injectBlobIds($mergedIndex, $localIndex);
        }

        return $mergedIndex;
    }

    /**
     * First stage object merging looking at primarily at pure existence.
     *
     * @param ConflictHandlerInterface $conflictHandler
     * @param IndexObject $remoteObject
     * @param IndexObject $localObject
     * @param IndexObject $lastLocalObject
     * @param int $options
     * @return IndexObject
     */
    protected function mergeObject1(ConflictHandlerInterface $conflictHandler, ?IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject, int $options = 0): ?IndexObject
    {
        if ($remoteObject === null && $localObject === null)
        {
            // locally and remotely deleted
            return null;
        }
        elseif ($lastLocalObject === null)
        {
            if ($remoteObject === null)
            {
                // locally created
                return clone $localObject;
            }
            elseif ($localObject === null)
            {
                // remotely created
                return clone $remoteObject;
            }
            elseif ($remoteObject !== null && $localObject !== null)
            {
                // remotely and locally created
                return $this->resolveConflict($conflictHandler, $remoteObject, $localObject, $lastLocalObject);
            }
        }
        elseif ($remoteObject === null)
        {
            // remotely deleted and locally changed
            return $this->resolveConflict($conflictHandler, $remoteObject, $localObject, $lastLocalObject);
        }
        elseif ($localObject === null)
        {
            if ($remoteObject->equals($lastLocalObject))
            {
                // locally deleted
                return null;
            }
            else
            {
                // remotely changed and locally deleted
                return $this->resolveConflict($conflictHandler, $remoteObject, $localObject, $lastLocalObject);
            }
        }

        return $this->mergeObject2($conflictHandler, $remoteObject, $localObject, $lastLocalObject, $options);
    }

    /**
     * Second stage object merging.
     *
     * @param ConflictHandlerInterface $conflictHandler
     * @param IndexObject $remoteObject
     * @param IndexObject $localObject
     * @param IndexObject $lastLocalObject
     * @param int $options
     * @return IndexObject
     */
    protected function mergeObject2(ConflictHandlerInterface $conflictHandler, IndexObject $remoteObject, IndexObject $localObject, IndexObject $lastLocalObject, int $options = 0): IndexObject
    {
        if ($remoteObject->getType() !== $localObject->getType())
        {
            return $this->resolveConflict($conflictHandler, $remoteObject, $localObject, $lastLocalObject);
        }

        $attributes = [
            'size' => null,
            'inode' => null,
            'blobId' => null,
            'hashes' => null,
        ];

        foreach (['permissions', 'mtime', 'linkTarget'] as $attribute)
        {
            $modifiedRemote = $lastLocalObject[$attribute] !== $remoteObject[$attribute];
            $modifiedLocal = $lastLocalObject[$attribute] !== $localObject[$attribute];

            if ($modifiedRemote && $modifiedLocal)
            {
                switch ($conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalObject))
                {
                    case ConflictHandlerInterface::USE_LOCAL: $modifiedRemote = false; break;
                    case ConflictHandlerInterface::USE_REMOTE: $modifiedLocal = false; break;
                    default: throw new \LogicException();
                }
            }

            if ($modifiedRemote || !$modifiedLocal)
            {
                $attributes[$attribute] = $remoteObject[$attribute];
            }
            else
            {
                $attributes[$attribute] = $localObject[$attribute];
            }
        }

        if ($localObject->isFile())
        {
            $remoteFileContentModified = $this->isRemoteFileContentModified($remoteObject, $lastLocalObject);
            $localFileContentModified = $this->isLocalFileContentModified($localObject, $lastLocalObject, $options);

            if ($remoteFileContentModified && $localFileContentModified)
            {
                switch ($conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalObject))
                {
                    case ConflictHandlerInterface::USE_LOCAL: $remoteFileContentModified = false; break;
                    case ConflictHandlerInterface::USE_REMOTE: $localFileContentModified = false; break;
                    default: throw new \LogicException();
                }
            }

            if ($remoteFileContentModified || !$localFileContentModified)
            {
                $attributes['size'] = $remoteObject->getSize();
                $attributes['blobId'] = $remoteObject->getBlobId();
                $attributes['hashes'] = clone $remoteObject->getHashes();
            }
            else
            {
                $attributes['size'] = $localObject->getSize();
                $attributes['inode'] = $localObject->getInode();
                $attributes['hashes'] = clone $localObject->getHashes();
            }
        }

        return new IndexObject(
            $localObject->getRelativePath(),
            $localObject->getType(),
            $attributes['mtime'],
            null,
            $attributes['permissions'],
            $attributes['size'],
            $attributes['inode'],
            $attributes['linkTarget'],
            $attributes['blobId'],
            $attributes['hashes']
        );
    }

    protected function isLocalFileContentModified(IndexObject $localObject, IndexObject $lastLocalObject, int $options): bool
    {
        assert($localObject->isFile());
        assert($lastLocalObject->isFile());

        $modified = false;
        $modified |= !$localObject->getHashes()->equals($lastLocalObject->getHashes());
        $modified |= $localObject->getSize() !== $lastLocalObject->getSize();
        $modified |= $localObject->getInode() !== $lastLocalObject->getInode();
        $modified |= $localObject->getMtime() !== $lastLocalObject->getMtime();

        $verifyContent = false;
        $verifyContent |= !$modified && $localObject->getCtime() !== $lastLocalObject->getCtime();
        $verifyContent |= !$modified && $options & static::VERIFY_CONTENT;

        if ($verifyContent)
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
                        $modified = true;
                    }
                }
            }
        }

        return $modified;
    }

    protected function isRemoteFileContentModified(IndexObject $remoteObject, IndexObject $lastLocalObject): bool
    {
        return $remoteObject->getBlobId() !== $lastLocalObject->getBlobId();
    }

    protected function resolveConflict(ConflictHandlerInterface $conflictHandler, IndexObject $remoteObject, ?IndexObject $localObject, ?IndexObject $lastLocalObject): IndexObject
    {
        $this->logger->notice("Resolving conflict at {$remoteObject->getRelativePath()}...");

        assert(($localObject === null) || ($localObject->getRelativePath() === $remoteObject->getRelativePath()));
        assert(($lastLocalObject === null) || ($lastLocalObject->getRelativePath() === $remoteObject->getRelativePath()));

        $solution = $conflictHandler->handleConflict($remoteObject, $localObject, $lastLocalObject);

        switch ($solution)
        {
            case ConflictHandlerInterface::USE_LOCAL:

                $this->logger->info("Using local version for conflict at {$remoteObject->getRelativePath()}");

                $return = clone $localObject;

                break;

            case ConflictHandlerInterface::USE_REMOTE:

                $this->logger->info("Using remote version for conflict at {$remoteObject->getRelativePath()}");

                $return = clone $remoteObject;

                break;

            default:

                throw new \LogicException();
        }

        return $return;
    }

    protected function injectBlobIds(Index $mergedIndex, Index $localIndex): void
    {
        foreach ($mergedIndex as $object)
        {
            /** @var IndexObject $object*/

            if ($object->getBlobId() !== null)
            {
                if ($localObject = $localIndex->getObjectByPath($object->getRelativePath()))
                {
                    $localObject->setBlobId($object->getBlobId());
                }
            }
        }
    }

    public static function getOptionsDebugString(int $options): string
    {
        $strings = [];

        if ($options & static::VERIFY_CONTENT)
        {
            $strings[] = 'VERIFY_CONTENT';
        }

        return empty($strings) ? '-' : implode(',', $strings);
    }
}
