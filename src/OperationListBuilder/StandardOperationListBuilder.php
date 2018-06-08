<?php

namespace Storeman\OperationListBuilder;

use Storeman\Exception\Exception;
use Storeman\Index;
use Storeman\IndexObject;
use Storeman\Operation\ChmodOperation;
use Storeman\Operation\DownloadOperation;
use Storeman\Operation\MkdirOperation;
use Storeman\Operation\SymlinkOperation;
use Storeman\Operation\TouchOperation;
use Storeman\Operation\UnlinkOperation;
use Storeman\Operation\UploadOperation;
use Storeman\OperationList;

class StandardOperationListBuilder implements OperationListBuilderInterface
{
    public function buildOperationList(Index $mergedIndex, Index $localIndex, Index $remoteIndex = null): OperationList
    {
        $uploadStreamFilters = [
            'zlib.deflate' => []
        ];
        $downloadStreamFilters = [
            'zlib.inflate' => []
        ];


        $operationList = new OperationList();

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
                $operationList->addOperation(new UnlinkOperation($mergedIndexObject->getRelativePath()));

                $modifiedPaths[] = $mergedIndexObject->getRelativePath();
            }


            if ($mergedIndexObject->isDirectory())
            {
                if ($localObject === null || !$localObject->isDirectory())
                {
                    $operationList->addOperation(new MkdirOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));

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
                $doDownloadFile = $doDownloadFile || ($localObject !== null && $mergedIndexObject->getBlobId() !== $localObject->getBlobId());

                if ($doDownloadFile)
                {
                    $operationList->addOperation(new DownloadOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getBlobId(), $downloadStreamFilters));
                    $operationList->addOperation(new TouchOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMtime()));
                    $operationList->addOperation(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));

                    $modifiedPaths[] = $mergedIndexObject->getRelativePath();
                }

                // local file got created or updated
                elseif ($remoteObject === null || $mergedIndexObject->getBlobId() === null)
                {
                    // generate blob id
                    // todo: we might want to have some mechanism to prevent overriding existing file in case of collision
                    $newBlobId = $mergedIndex->generateNewBlobId();

                    $mergedIndexObject->setBlobId($newBlobId);

                    $operationList->addOperation(new UploadOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getBlobId(), $uploadStreamFilters));
                }
            }

            elseif ($mergedIndexObject->isLink())
            {
                if ($localObject !== null && $localObject->getLinkTarget() !== $mergedIndexObject->getLinkTarget())
                {
                    $operationList->addOperation(new UnlinkOperation($mergedIndexObject->getRelativePath()));
                    $operationList->addOperation(new SymlinkOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getLinkTarget(), $mergedIndexObject->getMode()));

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
                $operationList->addOperation(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMode()));
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $operationList->addOperation(new UnlinkOperation($localObject->getRelativePath()));

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
            $operationList->addOperation(new TouchOperation($relativePath, $mtime));
        }

        return $operationList;
    }
}
