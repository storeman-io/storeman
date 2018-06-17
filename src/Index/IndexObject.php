<?php

namespace Storeman\Index;

use Storeman\Exception\Exception;

/**
 * An index object is the representation of one of this filesystem primitives contained in the index.
 */
class IndexObject
{
    public const TYPE_DIR = 1;
    public const TYPE_FILE = 2;
    public const TYPE_LINK = 3;


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

    public function getBasename(): string
    {
        $pos = strrpos($this->relativePath, DIRECTORY_SEPARATOR);

        return ($pos === false) ? $this->relativePath : substr($this->relativePath, $pos + 1);
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

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getLinkTarget(): ?string
    {
        return $this->linkTarget;
    }

    public function getBlobId(): ?string
    {
        return $this->blobId;
    }

    public function setBlobId(string $blobId): IndexObject
    {
        $this->blobId = $blobId;

        return $this;
    }

    /**
     * Equality check with all attributes.
     *
     * @param IndexObject $other
     * @return bool
     */
    public function equals(?IndexObject $other): bool
    {
        if (!$this->equalsOnDisk($other))
        {
            return false;
        }

        $equals = true;
        $equals = $equals && ($this->blobId === $other->blobId);

        return $equals;
    }

    /**
     * Comparison without blobId.
     *
     * @param IndexObject $other
     * @return bool
     */
    public function equalsOnDisk(?IndexObject $other): bool
    {
        if ($other === null)
        {
            return false;
        }

        $equal = true;
        $equal = $equal && ($this->relativePath === $other->relativePath);
        $equal = $equal && ($this->type === $other->type);
        $equal = $equal && ($this->mtime === $other->mtime);
        $equal = $equal && ($this->ctime === $other->ctime);
        $equal = $equal && ($this->mode === $other->mode);
        $equal = $equal && ($this->size === $other->size);
        $equal = $equal && ($this->linkTarget === $other->linkTarget);

        return $equal;
    }

    public function toScalarArray(): array
    {
        return [
            $this->relativePath,
            $this->type,
            $this->mtime,
            $this->ctime,
            $this->mode,
            $this->size,
            $this->blobId,
            $this->linkTarget,
        ];
    }

    public static function fromScalarArray(array $array): IndexObject
    {
        $object = new static;
        $object->relativePath = $array[0];
        $object->type = (int)$array[1];
        $object->mtime = (int)$array[2];
        $object->ctime = (int)$array[3];
        $object->mode = (int)$array[4];
        $object->size = (int)$array[5];
        $object->blobId = $array[6];
        $object->linkTarget = $array[7];

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
            throw new Exception("lstat() failed for {$absolutePath}");
        }

        $object = new static;
        $object->relativePath = $relativePath;
        $object->mtime = $stat['mtime'];
        $object->ctime = $stat['ctime'];
        $object->mode = $stat['mode'];

        if (is_link($absolutePath))
        {
            $object->type = static::TYPE_LINK;
            $object->linkTarget = str_replace($basePath, '', readlink($absolutePath));
        }
        elseif (is_file($absolutePath))
        {
            $object->type = static::TYPE_FILE;
            $object->size = (int)$stat['size'];
        }
        elseif (is_dir($absolutePath))
        {
            $object->type = static::TYPE_DIR;
        }
        else
        {
            throw new \LogicException();
        }

        return $object;
    }
}
