<?php

namespace Storeman;

use Clue\StreamFilter;
use Storeman\Config\Configuration;
use Storeman\Hash\AggregateHashAlgorithm;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Index\IndexObject;

/**
 * Provides read streams for index objects.
 */
class FileReader
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var HashAlgorithmInterface[]
     */
    protected $hashFunctions;

    public function __construct(Configuration $configuration, array $hashFunctions)
    {
        $this->configuration = $configuration;
        $this->hashFunctions = $hashFunctions;
    }

    /**
     * Returns a readable stream for the given file index object.
     *
     * @param IndexObject $indexObject
     * @return resource
     */
    public function getReadStream(IndexObject $indexObject)
    {
        assert($indexObject->isFile());

        $absolutePath = "{$this->configuration->getPath()}/{$indexObject->getRelativePath()}";

        $stream = fopen($absolutePath, 'rb');

        if ($stream === false)
        {
            throw new Exception("fopen() failed for '{$absolutePath}'");
        }

        $this->setupStreamHashing($stream, $indexObject);

        return $stream;
    }

    /**
     * Sets up transparent file hashing.
     *
     * @param resource $stream
     * @param IndexObject $indexObject
     */
    protected function setupStreamHashing($stream, IndexObject $indexObject)
    {
        $knownHashes = iterator_to_array($indexObject->getHashes());
        $configuredHashes = $this->configuration->getFileChecksums();

        if ($missingHashes = array_diff_key($configuredHashes, $knownHashes))
        {
            $aggregateHashAlgorithm = new AggregateHashAlgorithm(array_intersect_key($this->hashFunctions, array_flip($missingHashes)));
            $aggregateHashAlgorithm->initialize();

            StreamFilter\prepend($stream, function(string $chunk = null) use ($aggregateHashAlgorithm, $indexObject) {

                // eof
                if ($chunk === null)
                {
                    $hashes = $aggregateHashAlgorithm->finalize();

                    foreach ($hashes as $algorithm => $hash)
                    {
                        $indexObject->getHashes()->addHash($algorithm, $hash);
                    }
                }

                // digest chunk
                else
                {
                    $aggregateHashAlgorithm->digest($chunk);
                }

                return $chunk;
            });
        }
    }
}
