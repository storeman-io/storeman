<?php

namespace Storeman\Hash;

use Storeman\Config\Configuration;
use Storeman\FileReader;
use Storeman\Hash\Algorithm\HashAlgorithmInterface;
use Storeman\Index\IndexObject;

final class HashProvider
{
    /**
     * @var FileReader
     */
    protected $fileReader;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var HashAlgorithmInterface[]
     */
    protected $algorithms = [];

    public function __construct(FileReader $fileReader, Configuration $configuration, array $algorithms)
    {
        $this->fileReader = $fileReader;
        $this->configuration = $configuration;
        $this->algorithms = $algorithms;
    }

    public function getHash(IndexObject $indexObject, string $algorithm): string
    {
        $hashes = $indexObject->getHashes();

        if (!$hashes->hasHash($algorithm))
        {
            $this->loadHashes($indexObject, [$algorithm]);
        }

        return $hashes->getHash($algorithm);
    }

    public function loadHashes(IndexObject $indexObject, array $algorithms): void
    {
        // prevent computation of already known hashes
        $algorithms = array_diff($algorithms, array_keys(iterator_to_array($indexObject->getHashes())));

        if ($unknownAlgorithms = array_diff($algorithms, array_keys($this->algorithms)))
        {
            throw new \InvalidArgumentException(sprintf('Unknown algorithm(s): %s', implode(',', $unknownAlgorithms)));
        }

        // prevent re-computation of hashes that are build on file read anyway
        $missingAlgorithms = array_diff($algorithms, $this->configuration->getFileChecksums());

        $this->doLoadHashes($indexObject, $missingAlgorithms);
    }

    protected function doLoadHashes(IndexObject $indexObject, array $algorithms): void
    {
        $hashFunction = new AggregateHashAlgorithm(array_intersect_key($this->algorithms, array_flip($algorithms)));
        $hashFunction->initialize();

        $fileHandle = $this->fileReader->getReadStream($indexObject);

        while (!feof($fileHandle))
        {
            $buffer = fread($fileHandle, 65536);

            if ($buffer === false)
            {
                throw new \RuntimeException("fread() failed");
            }

            $hashFunction->digest($buffer);
        }

        foreach ($hashFunction->finalize() as $algorithm => $hash)
        {
            $indexObject->getHashes()->addHash($algorithm, $hash);
        }

        fclose($fileHandle);
    }
}
