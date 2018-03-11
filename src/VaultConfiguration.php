<?php

namespace Archivr;

use Zend\Stdlib\ArraySerializableInterface;

class VaultConfiguration implements ArraySerializableInterface
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
    protected $adapter;

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
     * Identifier for the operation list builder to use.
     * Refers to identifiers known to the OperationListBuilderFactory.
     *
     * @var string
     */
    protected $operationListBuilder = 'standard';

    /**
     * Map with additional storageDriver- or lockAdapter-specific settings.
     *
     * @var array
     */
    protected $settings;

    public function __construct(string $storageDriver = 'unknown')
    {
        $this->setAdapter($storageDriver);
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

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setAdapter(string $adapter): VaultConfiguration
    {
        $this->adapter = $adapter;

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

    public function getOperationListBuilder(): string
    {
        return $this->operationListBuilder;
    }

    public function setOperationListBuilder(string $operationListBuilder): VaultConfiguration
    {
        $this->operationListBuilder = $operationListBuilder;

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

    /**
     * {@inheritdoc}
     */
    public function exchangeArray(array $array)
    {
        if ($diff = array_diff(array_keys($array), array_keys($this->getArrayCopy())))
        {
            throw new \InvalidArgumentException("Invalid index(es): " . implode(',', $diff));
        }

        foreach ($array as $key => $value)
        {
            // using setter to prevent skipping validation
            call_user_func([$this, 'set' . ucfirst($key)], $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}
