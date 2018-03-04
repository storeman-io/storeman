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
    protected $lockAdapter = 'storage';

    /**
     * Identifier for the index merger to be used.
     * Refers to identifiers known to the IndexMergerFactory.
     *
     * @var string
     */
    protected $indexMerger = 'standard';

    /**
     * Identifier for the conflict handler to use.
     * Refers to identifiers known to the ConflictHandlerFactory.
     *
     * @var string
     */
    protected $conflictHandler = 'panicking';

    /**
     * Map with additional storageDriver- or lockAdapter-specific settings.
     *
     * @var array
     */
    protected $settings;

    public function __construct(string $storageDriver)
    {
        $this->storageDriver = $storageDriver;

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

    public function getIndexMerger(): string
    {
        return $this->indexMerger;
    }

    public function setIndexMerger(string $indexMerger): VaultConfiguration
    {
        $this->indexMerger = $indexMerger;

        return $this;
    }

    public function getConflictHandler(): string
    {
        return $this->conflictHandler;
    }

    public function setConflictHandler(string $conflictHandler): VaultConfiguration
    {
        $this->conflictHandler = $conflictHandler;

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
