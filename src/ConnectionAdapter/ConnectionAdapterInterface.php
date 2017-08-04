<?php

namespace Archivr\ConnectionAdapter;

interface ConnectionAdapterInterface
{
    /**
     * Reads and returns the content of a remote stored object.
     *
     * @param string $relativePath
     *
     * @return string
     */
    public function read(string $relativePath): string;

    /**
     * Writes the content of a remote stored object.
     *
     * @param string $relativePath
     * @param string $content
     */
    public function write(string $relativePath, string $content);

    /**
     * Returns true of the remote object exists.
     *
     * @param string $relativePath
     *
     * @return bool
     */
    public function exists(string $relativePath): bool;

    /**
     * Removes the remote stored object.
     *
     * @param string $relativePath
     */
    public function unlink(string $relativePath);

    /**
     * Opens and returns a PHP stream to a remote stored object that can be used to read/write to/from.
     *
     * @param string $relativePath
     * @param string $mode
     *
     * @return resource
     */
    public function getStream(string $relativePath, string $mode);
}
