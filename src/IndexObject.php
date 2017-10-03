<?php

namespace Archivr;

use Archivr\Exception\Exception;

class IndexObject
{
    const TYPE_DIR = 1;
    const TYPE_FILE = 2;
    const TYPE_LINK = 3;

    const CMP_INCLUDE_BLOB_ID = 1;
    const CMP_INCLUDE_CTIME = 2;

    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $mtime;

    /**
     * @var int
     */
    protected $ctime;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @var string
     */
    protected $linkTarget;

    /**
     * @var string
     */
    protected $blobId;

    protected function __construct() {}

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function isDirectory(): bool
    {
        return $this->type === static::TYPE_DIR;
    }

    public function isFile(): bool
    {
        return $this->type === static::TYPE_FILE;
    }

    public function isLink(): bool
    {
        return $this->type === static::TYPE_LINK;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function getCtime(): int
    {
        return $this->ctime;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getLinkTarget()
    {
        return $this->linkTarget;
    }

    public function getBlobId()
    {
        return $this->blobId;
    }

    public function setBlobId(string $blobId): IndexObject
    {
        $this->blobId = $blobId;

        return $this;
    }

    public function getIndexRecord(): array
    {
        return [$this->relativePath, $this->type, $this->mtime, $this->ctime, $this->mode, $this->blobId, $this->linkTarget];
    }

    public function equals(IndexObject $other = null, int $flags = 0): bool
    {
        if ($other === null)
        {
            return false;
        }

        $equals = true;
        $equals &= ($this->getRelativePath() === $other->getRelativePath());
        $equals &= ($this->getType() === $other->getType());
        $equals &= ($this->getMtime() === $other->getMtime());
        $equals &= ($this->getMode() === $other->getMode());
        $equals &= ($this->getLinkTarget() === $other->getLinkTarget());
        $equals &= (!($flags & self::CMP_INCLUDE_BLOB_ID) || ($this->getBlobId() === $other->getBlobId()));
        $equals &= (!($flags & self::CMP_INCLUDE_CTIME) || ($this->getCtime() === $other->getCtime()));

        return $equals;
    }

    public static function fromIndexRecord(array $row): IndexObject
    {
        $object = new static;
        $object->relativePath = $row[0];
        $object->type = (int)$row[1];
        $object->mtime = (int)$row[2];
        $object->ctime = (int)$row[3];
        $object->mode = (int)$row[4];
        $object->blobId = $row[5];
        $object->linkTarget = $row[6];

        return $object;
    }

    public static function fromPath(string $basePath, string $relativePath): IndexObject
    {
        $absolutePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        $object = new static;
        $object->relativePath = $relativePath;

        if (is_file($absolutePath))
        {
            $object->type = static::TYPE_FILE;
        }
        elseif (is_dir($absolutePath))
        {
            $object->type = static::TYPE_DIR;
        }
        elseif (is_link($absolutePath))
        {
            $object->type = static::TYPE_LINK;
            $object->linkTarget = str_replace($basePath, '', readlink($absolutePath));
        }
        else
        {
            throw new Exception(sprintf('File %s does not exist!', $absolutePath));
        }

        if (!($stat = lstat($absolutePath)))
        {
            throw new Exception();
        }

        $object->mtime = $stat['mtime'];
        $object->ctime = $stat['ctime'];
        $object->mode = $stat['mode'];

        return $object;
    }
}
