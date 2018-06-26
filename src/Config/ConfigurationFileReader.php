<?php

namespace Storeman\Config;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Storeman\ArrayUtils;
use Storeman\Container;
use Storeman\Exception;
use Storeman\PathUtils;
use Storeman\Validation\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigurationFileReader implements LoggerAwareInterface
{
    use LoggerAwareTrait;


    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container();
        $this->logger = new NullLogger();
    }

    public function getConfiguration(string $configurationFilePath, array $configurationDefaults = [], array $vaultConfigurationDefaults = []): Configuration
    {
        $this->logger->info("Reading config file from {$configurationFilePath}...");

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

        if (!array_key_exists('vaults', $array) || !is_array($array['vaults']))
        {
            throw new ConfigurationException("Missing vault configurations");
        }

        // merge defaults
        $array = ArrayUtils::merge($configurationDefaults, $array);
        $array['vaults'] = array_map(function($vaultConfig) use ($vaultConfigurationDefaults) {

            if (!is_array($vaultConfig))
            {
                throw new ConfigurationException();
            }

            return ArrayUtils::merge($vaultConfigurationDefaults, $vaultConfig);

        }, $array['vaults']);


        try
        {
            $configuration = new Configuration();
            $configuration->exchangeArray($array);
        }
        catch (\InvalidArgumentException $exception)
        {
            throw new ConfigurationException("In file {$configurationFilePath}", 0, $exception);
        }


        // validate configuration
        $constraintViolations = $this->getValidator()->validate($configuration);
        if ($constraintViolations->count())
        {
            $violation = $constraintViolations->get(0);

            throw new ConfigurationException("{$configurationFilePath}: {$violation->getPropertyPath()} - {$violation->getMessage()}");
        }

        $this->logger->info("The following configuration has been read: " . var_export($configuration->getArrayCopy(), true));

        return $configuration;
    }

    protected function getValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($this->container))
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();
    }
}
