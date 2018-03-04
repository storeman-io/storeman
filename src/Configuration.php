<?php

namespace Archivr;

use Archivr\Exception\Exception;

class Configuration
{
    /**
     * @var string
     */
    protected $localPath;

    /**
     * @var string[]
     */
    protected $exclusions = [];

    /**
     * @var string
     */
    protected $identity;

    /**
     * @var VaultConfiguration[]
     */
    protected $vaultConfigurations = [];

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
     *
     * @return VaultConfiguration
     */
    public function getVaultConfigurationByTitle(string $title)
    {
        return isset($this->vaultConfigurations[$title]) ? $this->vaultConfigurations[$title] : null;
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
