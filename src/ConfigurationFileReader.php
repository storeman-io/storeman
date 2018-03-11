<?php

namespace Archivr;

use Archivr\Exception\ConfigurationException;
use Archivr\Exception\Exception;
use Zend\Stdlib\ArrayUtils;

class ConfigurationFileReader
{
    public function getConfiguration(string $configurationFilePath)
    {
        $configurationFilePath = PathUtils::getAbsolutePath($configurationFilePath);

        if (!is_file($configurationFilePath) || !is_readable($configurationFilePath))
        {
            throw new Exception("Configuration file {$configurationFilePath} is not a readable file.");
        }

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

        $configuration = new Configuration($array['path']);
        $configuration->setIdentity(empty($array['identity']) ? sprintf('%s@%s', get_current_user(), gethostname()) : $array['identity']);

        if (!empty($array['exclude']))
        {
            if (!is_array($array['exclude']))
            {
                throw new ConfigurationException('Config key "exclude" has to be an array.');
            }

            $configuration->setExclude($array['exclude']);
        }

        foreach ($array['vaults'] as $index => $vaultConfig)
        {
            if (empty($vaultConfig['adapter']))
            {
                throw new ConfigurationException("Vault configuration #{$index} is missing the obligatory 'adapter' key.");
            }

            $vaultConfiguration = new VaultConfiguration($vaultConfig['adapter']);
            $vaultConfiguration->setSettings(empty($vaultConfig['settings']) ? [] : $vaultConfig['settings']);

            if (!empty($vaultConfig['title']))
            {
                $vaultConfiguration->setTitle($vaultConfig['title']);
            }

            if (!empty($vaultConfig['lockAdapter']))
            {
                $vaultConfiguration->setLockAdapter($vaultConfig['lockAdapter']);
            }

            if (!empty($vaultConfig['indexMerger']))
            {
                $vaultConfiguration->setIndexMerger($vaultConfig['indexMerger']);
            }

            if (!empty($vaultConfig['conflictHandler']))
            {
                $vaultConfiguration->setConflictHandler($vaultConfig['conflictHandler']);
            }

            $configuration->addVault($vaultConfiguration);
        }

        return $configuration;
    }
}
