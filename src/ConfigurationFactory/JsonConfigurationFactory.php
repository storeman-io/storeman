<?php

namespace Archivr\ConfigurationFactory;

use Archivr\Configuration;
use Archivr\ConnectionConfiguration;
use Archivr\Exception\ConfigurationException;
use Zend\Stdlib\ArrayUtils;

class JsonConfigurationFactory extends AbstractConfigurationFactory
{
    /**
     * @var string
     */
    protected $json;

    public function __construct(string $json)
    {
        $this->json = $json;
    }

    public function __invoke(): Configuration
    {
        $array = json_decode($this->json, true);

        $array = ArrayUtils::merge($this->getDefaults(), $array);

        foreach (['path', 'vaults'] as $requiredKey)
        {
            if (!array_key_exists($requiredKey, $array))
            {
                throw new ConfigurationException(sprintf('Missing config key: %s.', $requiredKey));
            }
        }

        if (!is_array($array['vaults']))
        {
            throw new ConfigurationException(sprintf('Configuration key \'vaults\' has to be an array.'));
        }

        if (empty($array['vaults']))
        {
            throw new ConfigurationException(sprintf('At least one vault configuration has to be present.'));
        }

        $configuration = new Configuration();
        $configuration->setLocalPath($array['path']);

        foreach ($array['vaults'] as $index => $vaultConfig)
        {
            if (empty($vaultConfig['adapter']))
            {
                throw new ConfigurationException(sprintf('Vault configuration #%d is missing the obligatory \'adapter\' key.', $index));
            }

            $name = $vaultConfig['adapter'];

            unset($vaultConfig['adapter']);

            $configuration->addConnectionConfiguration(new ConnectionConfiguration(trim($name), $vaultConfig));
        }

        return $configuration;
    }
}
