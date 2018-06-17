<?php

namespace Storeman\StorageAdapter;

interface StorageAdapterInterface
{
    /**
     * Writes the contents of a given stream to a remote object.
     *
     * @param string $relativePath
     * @param resource $stream
     */
    public function writeStream(string $relativePath, $stream);

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
     * Opens and returns a PHP stream that can be used to read from a remote object.
     *
     * @param string $relativePath
     *
     * @return resource
     */
    public function getReadStream(string $relativePath);
}
