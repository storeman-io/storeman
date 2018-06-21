<?php

namespace Storeman\Index;

use Storeman\Hash\HashContainer;

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
     * Full file mode.
     * May include additional modes like setuid, guid, etc.
     *
     * @var int
     */
    protected $mode;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $inode;

    /**
     * @var string
     */
    protected $linkTarget;

    /**
     * Content hashes for file index objects.
     *
     * @var HashContainer
     */
    protected $hashes;

    /**
     * @var string
     */
    protected $blobId;

    public function __construct(string $relativePath, int $type, int $mtime, int $ctime, int $mode, ?int $size, ?int $inode, ?string $linkTarget, ?string $blobId, ?HashContainer $hashContainer)
    {
        assert(($type === static::TYPE_FILE) ^ ($size === null));
        assert(($type === static::TYPE_FILE) || ($blobId === null));
        assert(($type === static::TYPE_FILE) ^ ($hashContainer === null));
        assert(($type === static::TYPE_LINK) ^ ($linkTarget === null));

        $this->relativePath = $relativePath;
        $this->type = $type;
        $this->mtime = $mtime;
        $this->ctime = $ctime;
        $this->mode = $mode;
        $this->size = $size;
        $this->inode = $inode;
        $this->linkTarget = $linkTarget;
        $this->blobId = $blobId;
        $this->hashes = $hashContainer;
    }

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

    public function getInode(): ?int
    {
        return $this->inode;
    }

    public function getLinkTarget(): ?string
    {
        return $this->linkTarget;
    }

    public function getHashes(): ?HashContainer
    {
        return $this->hashes;
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
        if ($other === null)
        {
            return false;
        }

        $equals = true;
        $equals = $equals && ($this->relativePath === $other->relativePath);
        $equals = $equals && ($this->type === $other->type);
        $equals = $equals && ($this->mtime === $other->mtime);
        $equals = $equals && ($this->ctime === $other->ctime);
        $equals = $equals && ($this->mode === $other->mode);
        $equals = $equals && ($this->size === $other->size);
        $equals = $equals && ($this->inode === $other->inode);
        $equals = $equals && ($this->linkTarget === $other->linkTarget);
        $equals = $equals && ($this->blobId === $other->blobId);

        if ($this->hashes)
        {
            $equals = $equals && $this->hashes->equals($other->hashes);
        }

        return $equals;
    }
}
