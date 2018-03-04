<?php

namespace Archivr;

class VaultConfiguration
{
    /**
     * An arbitrary user-defined title that helps to identity a vault by some user-specific information.
     *
     * @var string
     */
    protected $title;

    /**
     * Identifier for the vault adapter to use.
     *
     * @var string
     */
    protected $storageDriver;

    /**
     * Identifier for the lock adapter to use.
     *
     * @var string
     */
    protected $lockAdapter;

    /**
     * Map with additional storageDriver- or lockAdapter-specific settings.
     *
     * @var array
     */
    protected $settings;

    public function __construct(string $storageDriver, string $lockAdapter)
    {
        $this->storageDriver = $storageDriver;
        $this->lockAdapter = $lockAdapter;

        $this->setTitle($storageDriver);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): VaultConfiguration
    {
        $this->title = $title;

        return $this;
    }

    public function getStorageDriver(): string
    {
        return $this->storageDriver;
    }

    public function setStorageDriver(string $storageDriver): VaultConfiguration
    {
        $this->storageDriver = $storageDriver;

        return $this;
    }

    public function getLockAdapter(): string
    {
        return $this->lockAdapter;
    }

    public function setLockAdapter(string $lockAdapter): VaultConfiguration
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

    public function setSettings(array $settings): VaultConfiguration
    {
        $this->settings = $settings;

        return $this;
    }

    public function setSetting(string $name, $value): VaultConfiguration
    {
        $this->settings[$name] = $value;

        return $this;
    }
}
