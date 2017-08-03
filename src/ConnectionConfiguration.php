<?php

namespace Archivr;

class ConnectionConfiguration extends \ArrayObject
{
    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $title;

    /**
     * @param string $adapter
     * @param array $settingsMap
     * @param string $title
     */
    public function __construct(string $adapter, array $settingsMap, string $title = null)
    {
        $this->adapter = $adapter;
        $this->title = $title ?: $adapter;

        parent::__construct($settingsMap);
    }

    /**
     * @return string
     */
    public function getAdapter(): string
    {
        return $this->adapter;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}