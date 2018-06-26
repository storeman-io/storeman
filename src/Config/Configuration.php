<?php

namespace Storeman\Config;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Zend\Stdlib\ArraySerializableInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Configuration implements ArraySerializableInterface
{
    /**
     * The local base path of the archive.
     *
     * @var string
     */
    protected $path = './';

    /**
     * Set of excluded paths.
     *
     * @var string[]
     */
    protected $exclude = [];

    /**
     * Identity to be visible in synchronization log.
     *
     * @var string
     */
    protected $identity = 'unknown';

    /**
     * Identifier of the index builder to use.
     *
     * @var string
     */
    protected $indexBuilder = 'standard';

    /**
     * List of file checksums to be used for integrity checks and modification detection.
     *
     * @var string[]
     */
    protected $fileChecksums = ['sha256', 'sha1', 'md5'];

    /**
     * Array of vault configurations.
     *
     * @var VaultConfiguration[]
     */
    protected $vaults = [];

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Configuration
     */
    public function setPath(string $path): Configuration
    {
        if (substr($path, -1) !== '/')
        {
            $path .= '/';
        }

        $this->path = $path;

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * @param \string[] $paths
     *
     * @return Configuration
     */
    public function setExclude(array $paths): Configuration
    {
        $this->exclude = array_values($paths);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return Configuration
     */
    public function addExclusion(string $path): Configuration
    {
        $this->exclude[] = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->identity;
    }

    /**
     * @param string $identity
     *
     * @return Configuration
     */
    public function setIdentity(string $identity): Configuration
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndexBuilder(): string
    {
        return $this->indexBuilder;
    }

    /**
     * @param string $indexBuilder
     * @return $this
     */
    public function setIndexBuilder(string $indexBuilder): Configuration
    {
        $this->indexBuilder = $indexBuilder;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFileChecksums(): array
    {
        return $this->fileChecksums;
    }

    /**
     * @param string[] $fileChecksums
     * @return Configuration
     */
    public function setFileChecksums(array $fileChecksums): Configuration
    {
        $this->fileChecksums = array_values($fileChecksums);

        return $this;
    }

    /**
     * @return VaultConfiguration[]
     */
    public function getVaults(): array
    {
        return $this->vaults;
    }

    /**
     * @param string $title
     *
     * @return VaultConfiguration
     */
    public function getVaultByTitle(string $title): VaultConfiguration
    {
        if ($vaultConfiguration = $this->getVaultConfigurationByTitle($title))
        {
            return $vaultConfiguration;
        }

        throw new \InvalidArgumentException("Unknown vault configuration requested: {$title}");
    }

    public function addVault(VaultConfiguration $configuration): Configuration
    {
        if (array_search($configuration, $this->vaults) === false)
        {
            $this->vaults[] = $configuration;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function exchangeArray(array $array)
    {
        if ($diff = array_diff(array_keys($array), array_keys(get_object_vars($this))))
        {
            throw new \InvalidArgumentException("Invalid index(es): " . implode(',', $diff));
        }

        foreach ($array as $key => $value)
        {
            if ($key === 'vaults')
            {
                if (!is_array($value))
                {
                    throw new \InvalidArgumentException();
                }

                $this->vaults = [];

                foreach ($value as $val)
                {
                    if (!is_array($val))
                    {
                        throw new \InvalidArgumentException();
                    }

                    $vaultConfig = new VaultConfiguration($this);
                    $vaultConfig->exchangeArray($val);
                }
            }
            else
            {
                // using setter to prevent skipping validation
                call_user_func([$this, 'set' . ucfirst($key)], $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayCopy()
    {
        $return = get_object_vars($this);
        $return['vaults'] = array_values(array_map(function(VaultConfiguration $vaultConfiguration) {

            return $vaultConfiguration->getArrayCopy();

        }, $this->vaults));

        return $return;
    }

    protected function getVaultConfigurationByTitle(string $title): ?VaultConfiguration
    {
        foreach ($this->vaults as $vaultConfiguration)
        {
            if ($vaultConfiguration->getTitle() === $title)
            {
                return $vaultConfiguration;
            }
        }

        return null;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('path', new Assert\NotBlank());
        $metadata->addPropertyConstraint('identity', new Assert\NotBlank());
        $metadata->addPropertyConstraint('vaults', new Assert\Count(['min' => 1]));
    }
}
