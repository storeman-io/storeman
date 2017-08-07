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
     * @var ConnectionConfiguration[]
     */
    protected $connectionConfigurations = [];

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
     * @return ConnectionConfiguration[]
     */
    public function getConnectionConfigurations(): array
    {
        return $this->connectionConfigurations;
    }

    /**
     * @param string $title
     *
     * @return ConnectionConfiguration
     */
    public function getConnectionConfigurationByTitle(string $title)
    {
        return isset($this->connectionConfigurations[$title]) ? $this->connectionConfigurations[$title] : null;
    }

    /**
     * @param ConnectionConfiguration[] $connectionConfigurations
     *
     * @return Configuration
     */
    public function setConnectionConfigurations(array $connectionConfigurations): Configuration
    {
        $this->connectionConfigurations = [];

        foreach ($connectionConfigurations as $connectionConfiguration)
        {
            $this->addConnectionConfiguration($connectionConfiguration);
        }

        return $this;
    }

    /**
     * @param ConnectionConfiguration $configuration
     *
     * @return Configuration
     * @throws Exception
     */
    public function addConnectionConfiguration(ConnectionConfiguration $configuration): Configuration
    {
        if (isset($this->connectionConfigurations[$configuration->getTitle()]))
        {
            throw new Exception(sprintf('Trying to add connection configration with duplicate title %s.', $configuration->getTitle()));
        }

        $this->connectionConfigurations[$configuration->getTitle()] = $configuration;

        return $this;
    }
}
