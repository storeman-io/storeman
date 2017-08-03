<?php

namespace Archivr\ConfigurationFactory;

class JsonFileConfigurationFactory extends JsonConfigurationFactory
{
    public function __construct($path)
    {
        if (!is_file($path) || !is_readable($path))
        {
            throw new \InvalidArgumentException(sprintf('Given config file path %s is invalid.', $path));
        }

        parent::__construct(file_get_contents($path));

        $this->setDefault('path', dirname($path));
    }
}