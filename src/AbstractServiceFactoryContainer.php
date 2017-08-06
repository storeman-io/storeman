<?php

namespace Archivr;

abstract class AbstractServiceFactoryContainer
{
    protected $map;

    /**
     * @param callable[] $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    public function register(string $name, callable $factoryClosure): AbstractServiceFactoryContainer
    {
        $this->map[$name] = $factoryClosure;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->map[$name]);
    }
}
