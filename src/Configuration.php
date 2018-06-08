<?php

namespace Archivr;

use Archivr\Exception\Exception;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Zend\Stdlib\ArraySerializableInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Configuration implements ArraySerializableInterface
{
    public const VAULT_CONFIG_CLASS = VaultConfiguration::class;


    /**
     * The local base path of the archive.
     *
     * @var string
     */
    protected $path;

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
    protected $identity;

    /**
     * Map of vault configurations by identifier.
     *
     * @var VaultConfiguration[]
     */
    protected $vaults = [];

    public function __construct(string $localPath = './')
    {
        $this->setPath($localPath);
    }

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
        if (substr($path, -1) !== DIRECTORY_SEPARATOR)
        {
            $path .= DIRECTORY_SEPARATOR;
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
    public function getIdentity()
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
     * @return VaultConfiguration[]
     */
    public function getVaults(): array
    {
        return $this->vaults;
    }

    /**
     * @param string $title
     * @return bool
     */
    public function hasVault(string $title): bool
    {
        return isset($this->vaults[$title]);
    }

    /**
     * @param string $title
     *
     * @return VaultConfiguration
     */
    public function getVaultByTitle(string $title)
    {
        if (!isset($this->vaults[$title]))
        {
            throw new \InvalidArgumentException("Unknown vault configuration requested: {$title}");
        }

        return $this->vaults[$title];
    }

    /**
     * @param VaultConfiguration $configuration
     *
     * @return Configuration
     * @throws Exception
     */
    public function addVault(VaultConfiguration $configuration): Configuration
    {
        if (isset($this->vaults[$configuration->getTitle()]))
        {
            throw new Exception(sprintf('Trying to add vault with duplicate title %s.', $configuration->getTitle()));
        }

        $this->vaults[$configuration->getTitle()] = $configuration;

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

                    $className = static::VAULT_CONFIG_CLASS;

                    /** @var VaultConfiguration $vaultConfig */
                    $vaultConfig = new $className();
                    $vaultConfig->exchangeArray($val);

                    $this->addVault($vaultConfig);
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('path', new Assert\NotBlank());
        $metadata->addPropertyConstraint('identity', new Assert\NotBlank());
        $metadata->addPropertyConstraint('vaults', new Assert\Count(['min' => 1]));
    }
}
