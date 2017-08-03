<?php

namespace Archivr;

class Configuration
{
    /**
     * @var string
     */
    protected $localPath;

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
     */
    public function addConnectionConfiguration(ConnectionConfiguration $configuration): Configuration
    {
        if (isset($this->connectionConfigurations[$configuration->getTitle()]))
        {
            throw new \InvalidArgumentException(sprintf('Trying to add connection configration with duplicate title %s.', $configuration->getTitle()));
        }

        $this->connectionConfigurations[$configuration->getTitle()] = $configuration;

        return $this;
    }
}
