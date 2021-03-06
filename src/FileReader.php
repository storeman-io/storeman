<?php

namespace Storeman;

use Clue\StreamFilter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Storeman\Config\Configuration;
use Storeman\Hash\AggregateHashAlgorithm;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Index\IndexObject;

/**
 * Provides read streams for index objects.
 */
class FileReader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        $this->logger = new NullLogger();
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

        $this->logger->debug("Setting up read-stream for {$absolutePath}");

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
            $this->logger->debug("Setting up stream hashing for missing hashes: " . implode(',', $missingHashes));

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

            }, STREAM_FILTER_READ);
        }
    }
}
