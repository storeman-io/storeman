<?php

namespace Archivr;

use Archivr\Exception\ConfigurationException;
use Archivr\Exception\Exception;
use Symfony\Component\Validator\Validation;

class ConfigurationFileReader
{
    public function getConfiguration(string $configurationFilePath)
    {
        $configurationFilePath = PathUtils::getAbsolutePath($configurationFilePath);

        if (!is_file($configurationFilePath) || !is_readable($configurationFilePath))
        {
            throw new Exception("Configuration file {$configurationFilePath} is not a readable file.");
        }

        if (($json = file_get_contents($configurationFilePath)) === false)
        {
            throw new Exception("Failed to read config file {$configurationFilePath}.");
        }

        if (($array = json_decode($json, true)) === null)
        {
            throw new Exception("Malformed configuration file: {$configurationFilePath}.");
        }


        // default values
        $array = ArrayUtils::merge([
            'path' => dirname($configurationFilePath),
            'identity' => sprintf('%s@%s', get_current_user(), gethostname()),
        ], $array);


        try
        {
            $configuration = new Configuration();
            $configuration->exchangeArray($array);
        }
        catch (\InvalidArgumentException $exception)
        {
            throw new ConfigurationException('', 0, $exception);
        }


        // validate configuration
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $constraintViolations = $validator->validate($configuration);
        if ($constraintViolations->count())
        {
            $violation = $constraintViolations->get(0);

            throw new ConfigurationException("{$violation->getPropertyPath()} - {$violation->getMessage()}");
        }

        return $configuration;
    }
}
