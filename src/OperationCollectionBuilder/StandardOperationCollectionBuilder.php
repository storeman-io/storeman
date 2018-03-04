<?php

namespace Archivr\OperationCollectionBuilder;

use Archivr\Exception\Exception;
use Archivr\Index;
use Archivr\IndexObject;
use Archivr\Operation\ChmodOperation;
use Archivr\Operation\DownloadOperation;
use Archivr\Operation\MkdirOperation;
use Archivr\Operation\SymlinkOperation;
use Archivr\Operation\TouchOperation;
use Archivr\Operation\UnlinkOperation;
use Archivr\Operation\UploadOperation;
use Archivr\OperationCollection;

class StandardOperationCollectionBuilder implements OperationCollectionBuilderInterface
{
    public function buildOperationCollection(Index $mergedIndex, Index $localIndex, Index $remoteIndex = null): OperationCollection
    {
        $uploadStreamFilters = [
            'zlib.deflate' => []
        ];
        $downloadStreamFilters = [
            'zlib.inflate' => []
        ];


        $operationCollection = new OperationCollection();

        // mtimes to be set for directories are collected and applied afterwards as they get modified by synchronization operations as well
        $directoryMtimes = [];

        // set of modified paths that can be populated and is later used to add parent directory touch()es
        $modifiedPaths = [];

        // relies on the directory tree structure being traversed in pre-order (or at least a directory appears before its content)
        foreach ($mergedIndex as $mergedIndexObject)
        {
            /** @var IndexObject $mergedIndexObject */

            $localObject = $localIndex->getObjectByPath($mergedIndexObject->getRelativePath());
            $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($mergedIndexObject->getRelativePath()) : null;

            // unlink to-be-overridden local path with different type
            if ($localObject !== null && $localObject->getType() !== $mergedIndexObject->getType())
            {
                $operationCollection->addOperation(new UnlinkOperation($mergedIndexObject->getRelativePath()));

                $modifiedPaths[] = $mergedIndexObject->getRelativePath();
            }


            if ($mergedIndexObject->isDirectory())
            {
                if ($localObject === null || !$localObject->isDirectory())
                {
                    $operationCollection->addOperation(new MkdirOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));

                    $directoryMtimes[$mergedIndexObject->getRelativePath()] = $mergedIndexObject->getMtime();
                }

                if ($localObject !== null && $localObject->isDirectory())
                {
                    if ($localObject->getMtime() !== $mergedIndexObject->getMtime())
                    {
                        // fix wrong mtime
                        $directoryMtimes[$mergedIndexObject->getRelativePath()] = $mergedIndexObject->getMtime();
                    }
                }
            }

            elseif ($mergedIndexObject->isFile())
            {
                // local file did not exist, hasn't been a file before or is outdated
                $doDownloadFile = $localObject === null || !$localObject->isFile() || $localObject->getMtime() < $mergedIndexObject->getMtime();

                // file has to be restored as it does not equal the local version
                $doDownloadFile |= $localObject !== null && $mergedIndexObject->getBlobId() !== $localObject->getBlobId();

                if ($doDownloadFile)
                {
                    $operationCollection->addOperation(new DownloadOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getBlobId(), $downloadStreamFilters));
                    $operationCollection->addOperation(new TouchOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMtime()));
                    $operationCollection->addOperation(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));

                    $modifiedPaths[] = $mergedIndexObject->getRelativePath();
                }

                // local file got created or updated
                elseif ($remoteObject === null || $mergedIndexObject->getBlobId() === null)
                {
                    // generate blob id
                    // todo: we might want to have some mechanism to prevent overriding existing file in case of collision
                    $newBlobId = $mergedIndex->generateNewBlobId();

                    $mergedIndexObject->setBlobId($newBlobId);

                    $operationCollection->addOperation(new UploadOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getBlobId(), $uploadStreamFilters));
                }
            }

            elseif ($mergedIndexObject->isLink())
            {
                if ($localObject !== null && $localObject->getLinkTarget() !== $mergedIndexObject->getLinkTarget())
                {
                    $operationCollection->addOperation(new UnlinkOperation($mergedIndexObject->getRelativePath()));
                    $operationCollection->addOperation(new SymlinkOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getLinkTarget(), $mergedIndexObject->getMode()));

                    $modifiedPaths[] = $mergedIndexObject->getRelativePath();
                }
            }

            else
            {
                // unknown/invalid object type
                throw new Exception();
            }


            if ($localObject !== null && $localObject->getMode() !== $mergedIndexObject->getMode())
            {
                $operationCollection->addOperation(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $operationCollection->addOperation(new UnlinkOperation($localObject->getRelativePath()));

                $modifiedPaths[] = $localObject->getRelativePath();
            }
        }

        // add modified paths to directory mtimes to be set
        foreach ($modifiedPaths as $modifiedPath)
        {
            if (($dir = dirname($modifiedPath)) !== '.')
            {
                $dirObject = $mergedIndex->getObjectByPath($dir);

                $directoryMtimes[$dirObject->getRelativePath()] = $dirObject->getMtime();
            }
        }

        // set directory mtimes after all other modifications have been performed
        krsort($directoryMtimes);
        foreach ($directoryMtimes as $relativePath => $mtime)
        {
            $operationCollection->addOperation(new TouchOperation($relativePath, $mtime));
        }

        return $operationCollection;
    }
}
