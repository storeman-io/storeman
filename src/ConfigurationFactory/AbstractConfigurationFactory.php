<?php

namespace Archivr\ConfigurationFactory;

abstract class AbstractConfigurationFactory implements ConfigurationFactoryInterface
{
    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function setDefault(string $key, $value): AbstractConfigurationFactory
    {
        $this->defaults[$key] = $value;

        return $this;
    }
}
