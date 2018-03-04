<?php

namespace Archivr;

use Archivr\Exception\Exception;

abstract class AbstractFactory
{
    /**
     * Map from name to factory method.
     *
     * @var array
     */
    protected $factoryMap = [];

    /**
     * Can be set by concrete class to activate check for correct interface/class type on factory invocation.
     *
     * @var string
     */
    protected static $requiresInstanceOf;

    public function provides(string $name): bool
    {
        return isset($this->factoryMap[$name]);
    }

    public function create(string $name, ...$params)
    {
        if (!isset($this->factoryMap[$name]))
        {
            throw new \InvalidArgumentException(sprintf('FactoryContainer "%s" does not provide "%s".', get_class($this), $name));
        }

        if (!is_callable($this->factoryMap[$name]))
        {
            throw new \LogicException(sprintf('Factory method for "%s" is not callable.', $name));
        }

        $instance = call_user_func_array($this->factoryMap[$name], $params);

        // check for correct interface/class if requirement is set
        if (static::$requiresInstanceOf && !($instance instanceof static::$requiresInstanceOf))
        {
            throw new Exception(sprintf('Factory closure for "%s" does not return instance of %s. %s given.', $name, static::$requiresInstanceOf, is_object($instance) ? get_class($instance) : gettype($instance)));
        }

        return $instance;
    }
}
