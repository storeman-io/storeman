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

        $array = $this->readJsonFile($configurationFilePath);
        $array = $this->mergeDefaults($array, $configurationDefaults, $vaultConfigurationDefaults);

        try
        {
            $configuration = new Configuration();
            $configuration->exchangeArray($array);
        }
        catch (\InvalidArgumentException $exception)
        {
            throw new ConfigurationException("In file {$configurationFilePath}", 0, $exception);
        }

        $this->validateConfiguration($configuration, $configurationFilePath);

        $this->logger->info("The following configuration has been read: " . var_export($configuration->getArrayCopy(), true));

        return $configuration;
    }

    protected function readJsonFile(string $configurationFilePath): array
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

        if (!is_array($array))
        {
            throw new ConfigurationException("Malformed configuration file: {$configurationFilePath}");
        }

        return $array;
    }

    protected function mergeDefaults(array $config, array $configurationDefaults, array $vaultConfigurationDefaults): array
    {
        if (!array_key_exists('vaults', $config) || !is_array($config['vaults']))
        {
            throw new ConfigurationException("Missing vault configurations");
        }

        $config = ArrayUtils::merge($configurationDefaults, $config);
        $config['vaults'] = array_map(function($vaultConfig) use ($vaultConfigurationDefaults) {

            if (!is_array($vaultConfig))
            {
                throw new ConfigurationException();
            }

            return ArrayUtils::merge($vaultConfigurationDefaults, $vaultConfig);

        }, $config['vaults']);

        return $config;
    }

    protected function validateConfiguration(Configuration $configuration, string $configurationFilePath)
    {
        $constraintViolations = $this->getValidator()->validate($configuration);
        if ($constraintViolations->count())
        {
            $violation = $constraintViolations->get(0);

            throw new ConfigurationException("{$configurationFilePath}: {$violation->getPropertyPath()} - {$violation->getMessage()}");
        }
    }

    protected function getValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($this->container))
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();
    }
}
