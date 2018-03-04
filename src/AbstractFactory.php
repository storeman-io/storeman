<?php

namespace Archivr;

abstract class AbstractFactory
{
    /**
     * @var array
     */
    protected $factoryMap = [];

    public function provides(string $name): bool
    {
        return isset($this->factoryMap[$name]);
    }

    public function create(string $name, ...$params)
    {
        if (!isset($this->factoryMap[$name]))
        {
            throw new \InvalidArgumentException(sprintf("FactoryContainer %s does not provide %s.", get_class($this), $name));
        }

        return call_user_func_array($this->factoryMap[$name], $params);
    }
}
