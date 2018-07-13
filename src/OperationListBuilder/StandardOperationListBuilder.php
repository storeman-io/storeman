<?php

namespace Storeman\OperationListBuilder;

use Storeman\Exception;
use Storeman\Index\Index;
use Storeman\Index\IndexObject;
use Storeman\Operation\ChmodOperation;
use Storeman\Operation\DownloadOperation;
use Storeman\Operation\MkdirOperation;
use Storeman\Operation\SymlinkOperation;
use Storeman\Operation\TouchOperation;
use Storeman\Operation\UnlinkOperation;
use Storeman\OperationList;
use Storeman\OperationListItem;

class StandardOperationListBuilder implements OperationListBuilderInterface
{
    public function buildOperationList(Index $mergedIndex, Index $localIndex): OperationList
    {
        $operationList = new OperationList();

        // mtimes to be set for directories are collected and applied afterwards as they get modified by synchronization operations as well
        /** @var IndexObject[] $toSetMtime */
        $toSetMtime = [];

        // set of modified paths that can be populated and is later used to add parent directory touch()es
        $modifiedPaths = [];

        // paths to be removed
        /** @var IndexObject[] $toUnlink */
        $toUnlink = [];

        // relies on the directory tree structure being traversed in pre-order (or at least a directory appears before its content)
        foreach ($mergedIndex as $mergedIndexObject)
        {
            /** @var IndexObject $mergedIndexObject */

            $localObject = $localIndex->getObjectByPath($mergedIndexObject->getRelativePath());

            // unlink to-be-overridden local path with different type
            if ($localObject !== null && $localObject->getType() !== $mergedIndexObject->getType())
            {
                $toUnlink[] = $mergedIndexObject;
                $modifiedPaths[] = $mergedIndexObject->getRelativePath();
            }


            if ($mergedIndexObject->isDirectory())
            {
                if ($localObject === null || !$localObject->isDirectory())
                {
                    $operationList->add(new OperationListItem(new MkdirOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getPermissions()), $mergedIndexObject));

                    $toSetMtime[$mergedIndexObject->getRelativePath()] = $mergedIndexObject;
                }
            }

            elseif ($mergedIndexObject->isFile())
            {
                // local file did not exist, hasn't been a file before or has outdated content
                $doDownloadFile = $localObject === null || !$localObject->isFile() || $mergedIndexObject->getBlobId() !== $localObject->getBlobId();

                if ($doDownloadFile)
                {
                    $operationList->add(new OperationListItem(new DownloadOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getBlobId()), $mergedIndexObject));
                    $operationList->add(new OperationListItem(new TouchOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getMtime()), $mergedIndexObject));
                    $operationList->add(new OperationListItem(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getPermissions()), $mergedIndexObject));

                    $modifiedPaths[] = $mergedIndexObject->getRelativePath();
                }
            }

            elseif ($mergedIndexObject->isLink())
            {
                if ($localObject !== null && $localObject->getLinkTarget() !== $mergedIndexObject->getLinkTarget())
                {
                    $operationList->add(new OperationListItem(new UnlinkOperation($mergedIndexObject->getRelativePath()), $mergedIndexObject));
                    $operationList->add(new OperationListItem(new SymlinkOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getLinkTarget(), $mergedIndexObject->getPermissions()), $mergedIndexObject));

                    $modifiedPaths[] = $mergedIndexObject->getRelativePath();
                }
            }

            else
            {
                // unknown/invalid object type
                throw new Exception();
            }


            if ($localObject !== null)
            {
                if ($localObject->getPermissions() !== $mergedIndexObject->getPermissions())
                {
                    $operationList->add(new OperationListItem(new ChmodOperation($mergedIndexObject->getRelativePath(), $mergedIndexObject->getPermissions()), $mergedIndexObject));
                }

                if ($localObject->getMtime() !== $mergedIndexObject->getMtime())
                {
                    $toSetMtime[$localObject->getRelativePath()] = $mergedIndexObject;
                }
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $toUnlink[] = $localObject;
                $modifiedPaths[] = $localObject->getRelativePath();
            }
        }

        // add modified paths to directory mtimes to be set
        foreach ($modifiedPaths as $modifiedPath)
        {
            if (($dir = dirname($modifiedPath)) !== '.')
            {
                if ($dirObject = $mergedIndex->getObjectByPath($dir))
                {
                    $toSetMtime[$dirObject->getRelativePath()] = $dirObject;
                }
            }
        }

        // prepend deletions
        krsort($toUnlink);
        $unlinkOperations = new OperationList();
        foreach ($toUnlink as $indexObject)
        {
            $unlinkOperations->add(new OperationListItem(new UnlinkOperation($indexObject->getRelativePath()), $indexObject));
        }
        $operationList->prepend($unlinkOperations);

        // set directory mtimes after all other modifications have been performed
        krsort($toSetMtime);
        foreach ($toSetMtime as $relativePath => $indexObject)
        {
            $operationList->add(new OperationListItem(new TouchOperation($relativePath, $indexObject->getMtime()), $indexObject));
        }

        return $operationList;
    }
}
