<?php

namespace Archivr;

use Archivr\Exception\Exception;

abstract class AbstractFactory
{
    /**
     * Class is a singleton
     */
    private function __construct() {}

    protected static $factoryMaps = [];

    public static function create(string $name, ...$params)
    {
        if (!isset(static::$factoryMaps[static::class]))
        {
            static::$factoryMaps[static::class] = static::getFactoryMap();
        }

        $factoryMap = static::$factoryMaps[static::class];

        if (!isset($factoryMap[$name]))
        {
            throw new \InvalidArgumentException(sprintf('Factory "%s" does not provide "%s".', static::class, $name));
        }

        if (!is_callable($factoryMap[$name]))
        {
            throw new \LogicException(sprintf('Factory method in "%s" for "%s" is not callable.', static::class, $name));
        }

        $instance = call_user_func_array($factoryMap[$name], $params);

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
     * Can return an instance/class name that the factories have to return an instance of.
     *
     * @return string
     */
    protected static function requiresInstanceOf(): string
    {
        return null;
    }

    /**
     * Has to return map of names to factory closures.
     *
     * @return array
     */
    abstract protected static function getFactoryMap(): array;
}
