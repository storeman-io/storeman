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

    public const CMP_IGNORE_BLOBID = 1;
    public const CMP_IGNORE_INODE = 2;


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
     * mode & 0777
     *
     * @var int
     */
    protected $permissions;

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

    public function __construct(string $relativePath, int $type, int $mtime, int $ctime, int $permissions, ?int $size, ?int $inode, ?string $linkTarget, ?string $blobId, ?HashContainer $hashContainer)
    {
        assert(($type === static::TYPE_FILE) ^ ($size === null));
        assert(($type === static::TYPE_FILE) || ($blobId === null));
        assert(($type === static::TYPE_FILE) ^ ($hashContainer === null));
        assert(($type === static::TYPE_LINK) ^ ($linkTarget === null));
        assert(!($permissions & ~0777));

        $this->relativePath = $relativePath;
        $this->type = $type;
        $this->mtime = $mtime;
        $this->ctime = $ctime;
        $this->permissions = $permissions;
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

    public function getPermissions(): int
    {
        return $this->permissions;
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
     * @param int $options
     * @return bool
     */
    public function equals(?IndexObject $other, int $options = 0): bool
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
        $equals = $equals && ($this->permissions === $other->permissions);
        $equals = $equals && ($this->size === $other->size);
        $equals = $equals && (($options & static::CMP_IGNORE_INODE) || ($this->inode === $other->inode));
        $equals = $equals && ($this->linkTarget === $other->linkTarget);
        $equals = $equals && (($options & static::CMP_IGNORE_BLOBID) || ($this->blobId === $other->blobId));

        if ($this->hashes)
        {
            $equals = $equals && $this->hashes->equals($other->hashes);
        }

        return $equals;
    }
}
