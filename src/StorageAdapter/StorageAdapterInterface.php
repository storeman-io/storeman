<?php

namespace Storeman\StorageAdapter;

use Storeman\Config\VaultConfiguration;

/**
 * A storage adapter abstracts the specific API for a concrete storage provider.
 */
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

    /**
     * Returns some string that identifies the given vault configuration based on settings that cannot be changed without
     * creating a reference to another vault.
     *
     * @param VaultConfiguration $vaultConfiguration
     * @return string
     */
    public static function getIdentificationString(VaultConfiguration $vaultConfiguration): string;
}
