<?php

namespace Archivr;

use Archivr\Connection\ConnectionInterface;
use Archivr\IndexMerger\IndexMergerInterface;
use Archivr\IndexMerger\StandardIndexMerger;
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
     * @var ConnectionInterface
     */
    protected $vaultConnection;

    /**
     * @var string
     */
    protected $localPath;

    /**
     * @var IndexMergerInterface
     */
    protected $indexMerger;


    public function __construct(ConnectionInterface $vaultConnection, string $localPath)
    {
        $this->vaultConnection = $vaultConnection;
        $this->localPath = rtrim($this->expandTildePath($localPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function setIndexMerger(IndexMergerInterface $indexMerger = null)
    {
        $this->indexMerger = $indexMerger;

        return $this;
    }

    public function getIndexMerger(): IndexMergerInterface
    {
        if ($this->indexMerger === null)
        {
            $this->indexMerger = new StandardIndexMerger();
        }

        return $this->indexMerger;
    }

    // todo: this work should only be done once across all vaults within an ArchivR
    public function buildLocalIndex(): Index
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

        return $index;
    }

    // todo: file has to be unique across vaults in ArchivR
    public function loadLastLocalIndex()
    {
        $index = null;
        $path = $this->localPath . self::LAST_LOCAL_INDEX_FILE_NAME;

        if (is_file($path))
        {
            $stream = fopen($path, 'r');

            $index = $this->readIndexFromStream($stream, \DateTime::createFromFormat('U', filemtime($path)));

            fclose($stream);
        }

        return $index;
    }

    public function loadRemoteIndex()
    {
        $index = null;

        if ($this->vaultConnection->exists(static::REMOTE_INDEX_FILE_NAME))
        {
            $stream = $this->vaultConnection->getStream(static::REMOTE_INDEX_FILE_NAME, 'r');

            $index = $this->readIndexFromStream($stream);

            fclose($stream);
        }

        return $index;
    }

    public function buildMergedIndex(): Index
    {
        return $this->doBuildMergedIndex();
    }

    public function getOperationCollection(): OperationCollection
    {
        return $this->doGetOperationCollection();
    }

    public function synchronize(SynchronizationProgressListenerInterface $progressionListener = null): OperationResultCollection
    {
        // todo: profile to verify/falsify that this might have slight performance benefits due to less branching
        if ($progressionListener === null)
        {
            $progressionListener = new DummySynchronizationProgressListener();
        }

        $localIndex = $this->buildLocalIndex();
        $lastLocalIndex = $this->loadLastLocalIndex();

        $this->vaultConnection->acquireLock();

        $remoteIndex = $this->loadRemoteIndex();

        $mergedIndex = $this->doBuildMergedIndex($localIndex, $lastLocalIndex, $remoteIndex);
        $operationCollection = $this->doGetOperationCollection($localIndex, $remoteIndex, $mergedIndex);

        $operationResultCollection = new OperationResultCollection();

        // operation count + 2 index writes + lock release
        $progressionListener->start(count($operationCollection) + 2 + 1);

        foreach ($operationCollection as $operation)
        {
            /** @var OperationInterface $operation */

            $success = $operation->execute();

            $operationResult = new OperationResult($operation, $success);
            $operationResultCollection->addOperationResult($operationResult);

            $progressionListener->advance();
        }

        $this->writeIndexToStream($mergedIndex, $this->vaultConnection->getStream(static::REMOTE_INDEX_FILE_NAME, 'w'));

        $progressionListener->advance();

        $this->writeIndexToStream($mergedIndex, fopen($this->localPath . self::LAST_LOCAL_INDEX_FILE_NAME, 'w'));

        $progressionListener->advance();

        $this->vaultConnection->releaseLock();

        $progressionListener->advance();
        $progressionListener->finish();

        return $operationResultCollection;
    }

    protected function doBuildMergedIndex(Index $localIndex = null, Index $lastLocalIndex = null, Index $remoteIndex = null)
    {
        $localIndex = $localIndex ?: $this->buildLocalIndex();
        $lastLocalIndex = $lastLocalIndex ?: $this->loadLastLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->loadRemoteIndex();

        return $this->getIndexMerger()->merge($localIndex, $lastLocalIndex, $remoteIndex);
    }

    protected function doGetOperationCollection(Index $localIndex = null, Index $remoteIndex = null, Index $mergedIndex = null): OperationCollection
    {
        $localIndex = $localIndex ?: $this->buildLocalIndex();
        $remoteIndex = $remoteIndex ?: $this->loadRemoteIndex();
        $mergedIndex = $mergedIndex ?: $this->doBuildMergedIndex($localIndex, $remoteIndex);


        $operationCollection = new OperationCollection();

        $directoryMtimes = [];

        foreach ($mergedIndex as $indexObject)
        {
            /** @var IndexObject $indexObject */

            $absoluteLocalPath = $this->localPath . $indexObject->getRelativePath();

            $localObject = $localIndex->getObjectByPath($indexObject->getRelativePath());
            $remoteObject = $remoteIndex ? $remoteIndex->getObjectByPath($indexObject->getRelativePath()) : null;


            if ($localObject !== null && $localObject->getType() !== $indexObject->getType())
            {
                $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
            }


            if ($indexObject->isDirectory())
            {
                if ($localObject === null)
                {
                    $operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $indexObject->getMode()));
                }
                elseif (!$localObject->isDirectory())
                {
                    $operationCollection->addOperation(new MkdirOperation($absoluteLocalPath, $indexObject->getMode()));
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
                    $operationCollection->addOperation(new DownloadOperation($absoluteLocalPath, $indexObject->getBlobId(), $this->vaultConnection));
                    $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $indexObject->getMtime()));
                    $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $indexObject->getMode()));

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

                    $operationCollection->addOperation(new UploadOperation($absoluteLocalPath, $indexObject->getBlobId(), $this->vaultConnection));
                }
            }

            elseif ($indexObject->isLink())
            {
                $absoluteLinkTarget = dirname($absoluteLocalPath) . DIRECTORY_SEPARATOR . $indexObject->getLinkTarget();

                if ($localObject !== null && $localObject->getLinkTarget() !== $indexObject->getLinkTarget())
                {
                    $operationCollection->addOperation(new UnlinkOperation($absoluteLocalPath));
                    $operationCollection->addOperation(new SymlinkOperation($absoluteLocalPath, $absoluteLinkTarget, $indexObject->getMode()));
                }
            }

            else
            {
                // unknown object type
                throw new \RuntimeException();
            }


            if ($localObject !== null && $localObject->getMode() !== $indexObject->getMode())
            {
                $operationCollection->addOperation(new ChmodOperation($absoluteLocalPath, $indexObject->getMode()));
            }
        }

        // remove superfluous local files
        foreach ($localIndex as $localObject)
        {
            /** @var IndexObject $localObject */

            if ($mergedIndex->getObjectByPath($localObject->getRelativePath()) === null)
            {
                $operationCollection->addOperation(new UnlinkOperation($this->localPath . $localObject->getRelativePath()));
            }
        }

        // set directory mtimes
        foreach ($directoryMtimes as $absoluteLocalPath => $mtime)
        {
            $operationCollection->addOperation(new TouchOperation($absoluteLocalPath, $mtime));
        }

        return $operationCollection;
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
}