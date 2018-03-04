<?php

namespace Archivr;

use Archivr\Exception\Exception;

class Configuration
{
    /**
     * The local base path of the archive.
     *
     * @var string
     */
    protected $localPath;

    /**
     * Set of excluded paths.
     *
     * @var string[]
     */
    protected $exclusions = [];

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
    protected $vaultConfigurations = [];

    public function __construct(string $localPath)
    {
        $this->setLocalPath($localPath);
    }

    /**
     * @return string
     */
    public function getLocalPath(): string
    {
        return $this->localPath;
    }

    /**
     * @param string $localPath
     *
     * @return Configuration
     */
    public function setLocalPath(string $localPath): Configuration
    {
        if (substr($localPath, -1) !== DIRECTORY_SEPARATOR)
        {
            $localPath .= DIRECTORY_SEPARATOR;
        }

        $this->localPath = $localPath;

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getExclusions(): array
    {
        return $this->exclusions;
    }

    /**
     * @param \string[] $paths
     *
     * @return Configuration
     */
    public function setExclusions(array $paths): Configuration
    {
        $this->exclusions = array_values($paths);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return Configuration
     */
    public function addExclusion(string $path): Configuration
    {
        $this->exclusions[] = $path;

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
    public function getVaultConfigurations(): array
    {
        return $this->vaultConfigurations;
    }

    /**
     * @param string $title
     * @return bool
     */
    public function hasVaultConfiguration(string $title): bool
    {
        return isset($this->vaultConfigurations[$title]);
    }

    /**
     * @param string $title
     *
     * @return VaultConfiguration
     */
    public function getVaultConfigurationByTitle(string $title)
    {
        if (!isset($this->vaultConfigurations[$title]))
        {
            throw new \InvalidArgumentException("Unknown vault configuration requested: {$title}");
        }

        return $this->vaultConfigurations[$title];
    }

    /**
     * @param VaultConfiguration $configuration
     *
     * @return Configuration
     * @throws Exception
     */
    public function addVaultConfiguration(VaultConfiguration $configuration): Configuration
    {
        if (isset($this->vaultConfigurations[$configuration->getTitle()]))
        {
            throw new Exception(sprintf('Trying to add vault configration with duplicate title %s.', $configuration->getTitle()));
        }

        $this->vaultConfigurations[$configuration->getTitle()] = $configuration;

        return $this;
    }
}
