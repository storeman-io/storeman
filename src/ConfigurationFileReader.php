<?php

namespace Archivr;

use Archivr\Exception\ConfigurationException;
use Archivr\Exception\Exception;
use Zend\Stdlib\ArrayUtils;

class ConfigurationFileReader
{
    public function getConfiguration(string $configurationFilePath)
    {
        if (!is_file($configurationFilePath) || !is_readable($configurationFilePath))
        {
            throw new Exception(sprintf('Configuration file path "%s" does not exist or is not readable.', $configurationFilePath));
        }

        $configurationFilePath = realpath($configurationFilePath);

        $json = file_get_contents($configurationFilePath);

        if (!$json)
        {
            throw new Exception(sprintf('Failed to read config file "%s".', $configurationFilePath));
        }

        $array = json_decode($json, true);

        if ($array === null)
        {
            throw new Exception(sprintf('Invalid configuration file: %s.', $configurationFilePath));
        }

        $array = ArrayUtils::merge([
            'path' => dirname($configurationFilePath)
        ], $array);

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

        if (!empty($array['exclude']))
        {
            if (!is_array($array['exclude']))
            {
                throw new ConfigurationException('Config key "exclude" has to be an array.');
            }

            $configuration->setExclusions($array['exclude']);
        }

        foreach ($array['vaults'] as $index => $vaultConfig)
        {
            if (empty($vaultConfig['adapter']))
            {
                throw new ConfigurationException(sprintf('Vault configuration #%d is missing the obligatory \'adapter\' key.', $index));
            }

            $lockAdapter = empty($vaultConfig['lockAdapter']) ? 'connection' : $vaultConfig['lockAdapter'];

            $connectionConfig = new ConnectionConfiguration($vaultConfig['adapter'], $lockAdapter);
            $connectionConfig->setSettings($vaultConfig['settings'] ?: []);

            if (!empty($vaultConfig['title']))
            {
                $connectionConfig->setTitle($vaultConfig['title']);
            }

            $configuration->addConnectionConfiguration($connectionConfig);
        }

        return $configuration;
    }
}
