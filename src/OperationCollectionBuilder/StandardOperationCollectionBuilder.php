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
use Archivr\Vault;

class StandardOperationCollectionBuilder implements OperationCollectionBuilderInterface
{
    /**
     * @var Vault
     */
    protected $vault;

    public function __construct(Vault $vault)
    {
        $this->vault = $vault;
    }

    public function getVault(): Vault
    {
        return $this->vault;
    }

    public function buildOperationCollection(Index $mergedIndex, Index $localIndex, Index $remoteIndex = null): OperationCollection
    {
        $localPath = $this->vault->getLocalPath();
        $vaultConnection = $this->vault->getVaultConnection();


        $uploadStreamFilters = [
            'zlib.deflate' => []
        ];
        $downloadStreamFilters = [
            'zlib.inflate' => []
        ];


        $operationCollection = new OperationCollection();

        // mtimes to be set for directories are collected and applied afterwards as they get modified by synchronization operations as well
        $directoryMtimes = [];

        // relies on the directory tree structure being traversed in pre-order (or at least a directory appears before its content)
        foreach ($mergedIndex as $mergedIndexObject)
        {
            /** @var IndexObject $mergedIndexObject */

            $absoluteLocalPath = $localPath . $mergedIndexObject->getRelativePath();

            $localObject = $localIndex->getObjectByPath($mergedIndexObject->getRelativePath());
            $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($mergedIndexObject->getRelativePath()) : null;

            // unlink to-be-overridden local path with different type
            if ($localObject !== null && $localObject->getType() !== $mergedIndexObject->getType())
            {
                $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
            }


            if ($mergedIndexObject->isDirectory())
            {
                if ($localObject === null || !$localObject->isDirectory())
                {
                    $operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $mergedIndexObject->getMode()));

                    $directoryMtimes[$absoluteLocalPath] = $mergedIndexObject->getMtime();
                }

                if ($localObject !== null && $localObject->isDirectory())
                {
                    if ($localObject->getMtime() !== $mergedIndexObject->getMtime())
                    {
                        $directoryMtimes[$absoluteLocalPath] = $mergedIndexObject->getMtime();
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
                    $operationCollection->addOperation(new DownloadOperation($absoluteLocalPath, $mergedIndexObject->getBlobId(), $vaultConnection, $downloadStreamFilters));
                    $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mergedIndexObject->getMtime()));
                    $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $mergedIndexObject->getMode()));

                    $directoryMtimes[dirname($absoluteLocalPath)] = $mergedIndexObject->getMtime();
                }

                // local file got created or updated
                elseif ($remoteObject === null || $mergedIndexObject->getBlobId() === null)
                {
                    // generate blob id
                    do
                    {
                        $newBlobId = $mergedIndex->generateNewBlobId();
                    }
                    while ($vaultConnection->exists($newBlobId));

                    $mergedIndexObject->setBlobId($newBlobId);

                    $operationCollection->addOperation(new UploadOperation($absoluteLocalPath, $mergedIndexObject->getBlobId(), $vaultConnection, $uploadStreamFilters));
                }
            }

            elseif ($mergedIndexObject->isLink())
            {
                $absoluteLinkTarget = dirname($absoluteLocalPath) . DIRECTORY_SEPARATOR . $mergedIndexObject->getLinkTarget();

                if ($localObject !== null && $localObject->getLinkTarget() !== $mergedIndexObject->getLinkTarget())
                {
                    $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
                    $operationCollection->addOperation(new SymlinkOperation($absoluteLocalPath, $absoluteLinkTarget, $mergedIndexObject->getMode()));
                }
            }

            else
            {
                // unknown/invalid object type
                throw new Exception();
            }


            if ($localObject !== null && $localObject->getMode() !== $mergedIndexObject->getMode())
            {
                $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $mergedIndexObject->getMode()));
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $operationCollection->addOperation(new UnlinkOperation($localPath . $localObject->getRelativePath()));
            }
        }

        // set directory mtimes after all other modifications have been performed
        krsort($directoryMtimes);
        foreach ($directoryMtimes as $absoluteLocalPath => $mtime)
        {
            $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mtime));
        }

        return $operationCollection;
    }
}
