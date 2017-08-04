<?php

namespace Archivr;

class ConnectionConfiguration
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $vaultAdapter;

    /**
     * @var string
     */
    protected $lockAdapter;

    /**
     * @var array
     */
    protected $settings;

    public function __construct(string $vaultAdapter, string $lockAdapter)
    {
        $this->title = $vaultAdapter;
        $this->vaultAdapter = $vaultAdapter;
        $this->lockAdapter = $lockAdapter;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ConnectionConfiguration
    {
        $this->title = $title;

        return $this;
    }

    public function getVaultAdapter(): string
    {
        return $this->vaultAdapter;
    }

    public function setVaultAdapter(string $vaultAdapter): ConnectionConfiguration
    {
        $this->vaultAdapter = $vaultAdapter;

        return $this;
    }

    public function getLockAdapter(): string
    {
        return $this->lockAdapter;
    }

    public function setLockAdapter(string $lockAdapter): ConnectionConfiguration
    {
        $this->lockAdapter = $lockAdapter;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getSetting(string $name)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }

    public function setSettings(array $settings): ConnectionConfiguration
    {
        $this->settings = $settings;

        return $this;
    }

    public function setSetting(string $name, $value): ConnectionConfiguration
    {
        $this->settings[$name] = $value;

        return $this;
    }
}
