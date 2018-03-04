<?php

namespace Archivr;

use Archivr\Exception\Exception;

/**
 * An index object is the representation of one of this filesystem primitives contained in the index.
 */
class IndexObject
{
    const TYPE_DIR = 1;
    const TYPE_FILE = 2;
    const TYPE_LINK = 3;

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
     * @var int
     */
    protected $size;

    /**
     * @var string
     */
    protected $linkTarget;

    /**
     * @var string
     */
    protected $blobId;

    /**
     * Prevent construction not using static factory methods.
     */
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

    public function getSize()
    {
        return $this->size;
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
        return [$this->relativePath, $this->type, $this->mtime, $this->ctime, $this->mode, $this->size, $this->blobId, $this->linkTarget];
    }

    /**
     * todo: is the same as $this == $other
     *
     * @param IndexObject|null $other
     * @return bool
     */
    public function equals(IndexObject $other = null): bool
    {
        if ($other === null)
        {
            return false;
        }

        $equals = true;
        $equals = $equals && ($this->getRelativePath() === $other->getRelativePath());
        $equals = $equals && ($this->getType() === $other->getType());
        $equals = $equals && ($this->getMtime() === $other->getMtime());
        $equals = $equals && ($this->getCtime() === $other->getCtime());
        $equals = $equals && ($this->getMode() === $other->getMode());
        $equals = $equals && ($this->getSize() === $other->getSize());
        $equals = $equals && ($this->getLinkTarget() === $other->getLinkTarget());
        $equals = $equals && ($this->getBlobId() === $other->getBlobId());

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
        $object->size = (int)$row[5];
        $object->blobId = $row[6];
        $object->linkTarget = $row[7];

        return $object;
    }

    /**
     * Returns an instance representing the filesystem object that can be found under the given path.
     *
     * @param string $basePath
     * @param string $relativePath
     * @return IndexObject
     */
    public static function fromPath(string $basePath, string $relativePath): IndexObject
    {
        $absolutePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        clearstatcache(null, $absolutePath);

        if (!($stat = @lstat($absolutePath)))
        {
            throw new Exception();
        }

        $object = new static;
        $object->relativePath = $relativePath;
        $object->mtime = $stat['mtime'];
        $object->ctime = $stat['ctime'];
        $object->mode = $stat['mode'];

        if (is_file($absolutePath))
        {
            $object->type = static::TYPE_FILE;
            $object->size = (int)$stat['size'];
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
            throw new \LogicException();
        }

        return $object;
    }
}
