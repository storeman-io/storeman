<?php

namespace Archivr;

use Archivr\Connection\VaultConnectionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Archivr\Operation\ChmodOperation;
use Archivr\Operation\DownloadOperation;
use Archivr\Operation\MkdirOperation;
use Archivr\Operation\OperationInterface;
use Archivr\Operation\SymlinkOperation;
use Archivr\Operation\TouchOperation;
use Archivr\Operation\UnlinkOperation;
use Archivr\Operation\UploadOperation;

class Vault implements VaultInterface
{
    use TildeExpansionTrait;


    const LAST_LOCAL_INDEX_FILE_NAME = '.lastLocalIndex';
    const REMOTE_INDEX_FILE_NAME = 'index';


    /**
     * @var VaultConnectionInterface
     */
    protected $vaultConnection;

    /**
     * @var string
     */
    protected $localPath;

    /**
     * @var Index
     */
    protected $localIndex;

    /**
     * @var Index
     */
    protected $lastLocalIndex;

    /**
     * @var Index
     */
    protected $remoteIndex;

    /**
     * @var Index
     */
    protected $mergedIndex;

    /**
     * @var OperationCollection
     */
    protected $operationCollection;


    public function __construct(VaultConnectionInterface $vaultConnection, string $localPath)
    {
        $this->vaultConnection = $vaultConnection;
        $this->localPath = rtrim($this->expandTildePath($localPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function buildLocalIndex(): Index
    {
        if ($this->localIndex === null)
        {
            $finder = new Finder();
            $finder->in($this->localPath);
            $finder->ignoreDotFiles(false);
            $finder->ignoreVCS(true);

            $index = new Index();

            foreach ($finder->directories() as $fileInfo)
            {
                /** @var SplFileInfo $fileInfo */

                $index->addObject(IndexObject::fromPath($this->localPath, $fileInfo->getRelativePathname()));
            }

            foreach ($finder->files() as $fileInfo)
            {
                /** @var SplFileInfo $fileInfo */

                if ($fileInfo->getFilename() === Vault::LAST_LOCAL_INDEX_FILE_NAME)
                {
                    continue;
                }

                $index->addObject(IndexObject::fromPath($this->localPath, $fileInfo->getRelativePathname()));
            }

            $this->localIndex = $index;
        }

        return $this->localIndex;
    }

    public function loadLastLocalIndex()
    {
        if ($this->lastLocalIndex === null)
        {
            $path = $this->localPath . self::LAST_LOCAL_INDEX_FILE_NAME;

            if (is_file($path))
            {
                $stream = fopen($path, 'r');

                $this->lastLocalIndex = $this->readIndexFromStream($stream, \DateTime::createFromFormat('U', filemtime($path)));

                fclose($stream);
            }
        }


        return $this->lastLocalIndex;
    }

    public function loadRemoteIndex()
    {
        if ($this->remoteIndex === null)
        {
            if ($this->vaultConnection->exists(static::REMOTE_INDEX_FILE_NAME))
            {
                $this->vaultConnection->acquireLock();

                $stream = $this->vaultConnection->getStream(static::REMOTE_INDEX_FILE_NAME, 'r');

                $this->remoteIndex = $this->readIndexFromStream($stream);

                fclose($stream);
            }
        }

        return $this->remoteIndex;
    }

    public function buildMergedIndex(): Index
    {
        if ($this->mergedIndex === null)
        {
            $localIndex = $this->buildLocalIndex();
            $lastLocalIndex = $this->loadLastLocalIndex();
            $remoteIndex = $this->loadRemoteIndex();

            $this->mergedIndex = new Index();

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
                        $this->mergedIndex->addObject($localObject);
                    }
                    elseif ($remoteIndex === null)
                    {
                        $this->mergedIndex->addObject($localObject);
                    }
                }
                else
                {
                    // todo: merge mode by ctime

                    $remoteObjectModified = $remoteObject->getMtime() > $lastLocalIndex->getCreated()->getTimestamp();

                    if (!$localObjectModified)
                    {
                        $this->mergedIndex->addObject($remoteObject);
                    }

                    elseif (!$remoteObjectModified)
                    {
                        $this->mergedIndex->addObject($localObject);
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
                            $this->mergedIndex->addObject($remoteObject);
                        }
                    }
                }
            }
        }

        return $this->mergedIndex;
    }

    public function getOperationCollection(): OperationCollection
    {
        if ($this->operationCollection === null)
        {
            $localIndex = $this->buildLocalIndex();
            $remoteIndex = $this->loadRemoteIndex();
            $mergedIndex = $this->buildMergedIndex();


            $this->operationCollection = new OperationCollection();

            $directoryMtimes = [];

            foreach ($mergedIndex as $indexObject)
            {
                /** @var IndexObject $indexObject */

                $absoluteLocalPath = $this->localPath . $indexObject->getRelativePath();

                $localObject = $localIndex->getObjectByPath($indexObject->getRelativePath());
                $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($indexObject->getRelativePath()) : null;


                if ($localObject !== null && $localObject->getType() !== $indexObject->getType())
                {
                    $this->operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
                }


                if ($indexObject->isDirectory())
                {
                    if ($localObject === null)
                    {
                        $this->operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $indexObject->getMode()));
                    }
                    elseif (!$localObject->isDirectory())
                    {
                        $this->operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $indexObject->getMode()));
                    }

                    if ($localObject !== null && $localObject->isDirectory())
                    {
                        if ($localObject->getMtime() !== $indexObject->getMtime())
                        {
                            $directoryMtimes[$absoluteLocalPath] = $indexObject->getMtime();
                        }
                    }
                }

                elseif ($indexObject->isFile())
                {
                    // local file did not exist, hasn't been a file before or is outdated
                    if ($localObject === null || !$localObject->isFile() || $localObject->getMtime() < $indexObject->getMtime())
                    {
                        $this->operationCollection->addOperation(new DownloadOperation($absoluteLocalPath, $indexObject->getBlobId(), $this->vaultConnection));
                        $this->operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $indexObject->getMtime()));
                        $this->operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $indexObject->getMode()));

                        $directoryMtimes[dirname($absoluteLocalPath)] = $indexObject->getMtime();
                    }

                    // local file got updated
                    elseif ($remoteObject === null || $indexObject->getBlobId() !== $remoteObject->getBlobId())
                    {
                        // generate blob id
                        do
                        {
                            $blobId = $mergedIndex->generateNewBlobId();
                        }
                        while($this->vaultConnection->exists($blobId));

                        $indexObject->setBlobId($blobId);

                        $this->operationCollection->addOperation(new UploadOperation($absoluteLocalPath, $indexObject->getBlobId(), $this->vaultConnection));
                    }
                }

                elseif ($indexObject->isLink())
                {
                    $absoluteLinkTarget = dirname($absoluteLocalPath) . DIRECTORY_SEPARATOR . $indexObject->getLinkTarget();

                    if ($localObject !== null && $localObject->getLinkTarget() !== $indexObject->getLinkTarget())
                    {
                        $this->operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
                        $this->operationCollection->addOperation(new SymlinkOperation($absoluteLocalPath, $absoluteLinkTarget, $indexObject->getMode()));
                    }
                }

                else
                {
                    // unknown object type
                    throw new \RuntimeException();
                }


                if ($localObject !== null && $localObject->getMode() !== $indexObject->getMode())
                {
                    $this->operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $indexObject->getMode()));
                }
            }

            // remove superfluous local files
            foreach ($localIndex as $localObject)
            {
                /** @var IndexObject $localObject */

                if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
                {
                    $this->operationCollection->addOperation(new UnlinkOperation($this->localPath . $localObject->getRelativePath()));
                }
            }

            // set directory mtimes
            foreach ($directoryMtimes as $absoluteLocalPath => $mtime)
            {
                $this->operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mtime));
            }
        }

        return $this->operationCollection;
    }

    public function synchronize()
    {
        $mergedIndex = $this->buildMergedIndex();
        $operationCollection = $this->getOperationCollection();

        foreach ($operationCollection as $operation)
        {
            /** @var OperationInterface $operation */

            $success = $operation->execute();

            yield new OperationResult($operation, $success);
        }

        $this->writeIndexToStream($mergedIndex, $this->vaultConnection->getStream(static::REMOTE_INDEX_FILE_NAME, 'w'));
        $this->writeIndexToStream($mergedIndex, fopen($this->localPath . self::LAST_LOCAL_INDEX_FILE_NAME, 'w'));

        // is now outdated
        unset($this->remoteIndex);
        unset($this->lastLocalIndex);

        $this->vaultConnection->releaseLock();
    }

    protected function readIndexFromStream($stream, \DateTime $created = null): Index
    {
        $index = new Index($created);

        while(($row = fgetcsv($stream)) !== false)
        {
            $index->addObject(IndexObject::fromIndexRecord($row));
        }

        return $index;
    }

    protected function writeIndexToStream(Index $index, $stream)
    {
        foreach ($index as $object)
        {
            /** @var IndexObject $object */

            if (fputcsv($stream, $object->getIndexRecord()) === false)
            {
                throw new \RuntimeException();
            }
        }

        fclose($stream);
    }

    protected function downloadBlob(IndexObject $indexObject)
    {
        $localStream = fopen($this->localPath . $indexObject->getRelativePath(), 'w');
        $remoteStream = $this->vaultConnection->getStream($indexObject->getBlobId(), 'r');

        stream_copy_to_stream($remoteStream, $localStream);

        fclose($remoteStream);
        fclose($localStream);
    }

    protected function uploadBlob(IndexObject $indexObject)
    {
        $localStream = fopen($this->localPath . $indexObject->getRelativePath(), 'r');
        $remoteStream = $this->vaultConnection->getStream($indexObject->getBlobId(), 'w');

        stream_copy_to_stream($localStream, $remoteStream);

        fclose($remoteStream);
        fclose($localStream);
    }
}