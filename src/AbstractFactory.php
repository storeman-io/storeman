<?php

namespace Storeman;

use Storeman\Exception\Exception;

/**
 * todo: Consider replacement by reference implementation
 */
abstract class AbstractFactory
{
    /**
     * Class is a singleton
     */
    private function __construct() {}

    protected static $factoryMaps = [];

    /**
     * Returns true if this factory provides the service of the given name.
     *
     * @param string $name
     * @return bool
     */
    public static function provides(string $name): bool
    {
        static::loadFactoryMap();

        return isset(static::$factoryMaps[static::class][$name]);
    }

    /**
     * Returns array of all service names that this factory provides.
     *
     * @return array
     */
    public static function getProvidedServiceNames(): array
    {
        static::loadFactoryMap();

        return array_keys(static::$factoryMaps[static::class]);
    }

    /**
     * Creates and returns the service under the given name.
     *
     * @param string $name
     * @param array ...$params Parameters passed to the actual factory.
     * @return mixed
     * @throws Exception
     */
    public static function create(string $name, ...$params)
    {
        static::loadFactoryMap();

        $factoryMap = static::$factoryMaps[static::class];

        if (!isset($factoryMap[$name]))
        {
            throw new \InvalidArgumentException(sprintf('Factory "%s" does not provide "%s".', static::class, $name));
        }

        $factory = $factoryMap[$name];

        if (is_string($factory))
        {
            $instance = new $factory(...$params);
        }
        elseif ($factory instanceof \Closure)
        {
            $instance = $factory(...$params);
        }
        elseif (is_object($factory))
        {
            $instance = $factory;
        }
        else
        {
            throw new \LogicException();
        }

        // check for correct interface/class if requirement is set
        if ($type = static::requiresInstanceOf())
        {
            if (!($instance instanceof $type))
            {
                throw new Exception(sprintf('Factory closure for "%s" does not return instance of %s. %s given.', $name, $type, is_object($instance) ? get_class($instance) : gettype($instance)));
            }
        }

        return $instance;
    }

    /**
     * Registers a n
     *
     * @param string $name
     * @param string|\Closure|object $factory
     */
    public static function registerFactory(string $name, $factory): void
    {
        static::loadFactoryMap();

        if (is_string($factory))
        {
            if (!class_exists($factory))
            {
                throw new \RuntimeException(sprintf('Trying to register factory named "%s" to "%s" as class name "%s" which does not exist.', $name, static::class, $factory));
            }
        }
        elseif ($factory instanceof \Closure)
        {
            // cannot really validate
        }
        elseif (is_object($factory))
        {
            if ($type = static::requiresInstanceOf())
            {
                if (!($factory instanceof $type))
                {
                    throw new \RuntimeException(sprintf('Trying to register service instance named "%s" to "%s" as which is not an instance of "%s".', $name, static::class, $type));
                }
            }
        }
        else
        {
            throw new \InvalidArgumentException(sprintf('Invalid factory of type "%s" named "%s" given to "%s".', gettype($factory), $name, static::class));
        }

        static::$factoryMaps[static::class][$name] = $factory;
    }

    /**
     * Can return an instance/class name that the factories have to return an instance of.
     *
     * @return string
     */
    protected static function requiresInstanceOf(): string
    {
        return null;
    }

    protected static function loadFactoryMap(): void
    {
        if (!isset(static::$factoryMaps[static::class]))
        {
            static::$factoryMaps[static::class] = static::getFactoryMap();
        }
    }

    /**
     * Has to return map of names to factory closures.
     *
     * @return array
     */
    abstract protected static function getFactoryMap(): array;
}
