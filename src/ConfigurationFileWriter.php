<?php

namespace Storeman;

class ConfigurationFileWriter
{
    /**
     * @param Configuration $configuration
     * @param string $path File path to write the configuration file to.
     * @param bool $skipDefaults If true only settings that do not match the default are written.
     */
    public function writeConfigurationFile(Configuration $configuration, string $path, bool $skipDefaults = true): void
    {
        if (!file_put_contents($path, $this->buildConfigurationFile($configuration, $skipDefaults)))
        {
            throw new \RuntimeException("Cannot write configuration file to {$path}");
        }
    }

    /**
     * Serializes the given configuration to the content of an equivalent configuration file.
     *
     * @param Configuration $configuration
     * @param bool $skipDefaults If true only settings that do not match the default are written.
     * @return string
     */
    public function buildConfigurationFile(Configuration $configuration, bool $skipDefaults = true): string
    {
        $configArray = $configuration->getArrayCopy();

        if ($skipDefaults)
        {
            $nonDefaults = ArrayUtils::recursiveArrayDiff($configArray, (new Configuration())->getArrayCopy());

            $vaultConfigs = [];
            foreach ($configuration->getVaults() as $vaultConfiguration)
            {
                $vaultConfigs[] = ArrayUtils::recursiveArrayDiff($vaultConfiguration->getArrayCopy(), (new VaultConfiguration())->getArrayCopy());
            }

            $nonDefaults['vaults'] = $vaultConfigs;

            $configArray = $nonDefaults;
        }

        return json_encode($configArray, JSON_PRETTY_PRINT);
    }
}
