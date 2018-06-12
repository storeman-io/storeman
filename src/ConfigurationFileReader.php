<?php

namespace Storeman;

use Storeman\Exception\ConfigurationException;
use Storeman\Exception\Exception;
use Storeman\Validation\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigurationFileReader
{
    public const CONFIG_CLASS = Configuration::class;


    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?: new Container();
    }

    public function getConfiguration(string $configurationFilePath): Configuration
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
            $className = static::CONFIG_CLASS;

            /** @var Configuration $configuration */
            $configuration = new $className();
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
