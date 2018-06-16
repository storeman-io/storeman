<?php

namespace Storeman;

use Storeman\Validation\Constraints as StoremanAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Zend\Stdlib\ArraySerializableInterface;

class VaultConfiguration implements ArraySerializableInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * An arbitrary user-defined title that helps to identity a vault by some user-specific information.
     *
     * @var string
     */
    protected $title = 'unknown';

    /**
     * Identifier for the vault layout to use.
     *
     * @var string
     */
    protected $vaultLayout = 'amberjack';

    /**
     * Identifier for the vault adapter to use.
     *
     * @var string
     */
    protected $adapter = 'unknown';

    /**
     * Identifier for the lock adapter to use.
     *
     * @var string
     */
    protected $lockAdapter = 'storage';

    /**
     * Identifier for the index merger to be used.
     *
     * @var string
     */
    protected $indexMerger = 'standard';

    /**
     * Identifier for the conflict handler to use.
     *
     * @var string
     */
    protected $conflictHandler = 'panicking';

    /**
     * Identifier for the operation list builder to use.
     *
     * @var string
     */
    protected $operationListBuilder = 'standard';

    /**
     * The vault priority is used for cases where some data has to be read from a vault and a decision has to be made which vault to use.
     *
     * @var int
     */
    protected $priority = 0;

    /**
     * Map with additional component-specific settings.
     *
     * @var array
     */
    protected $settings = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->configuration->addVault($this);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
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

    public function getVaultLayout(): string
    {
        return $this->vaultLayout;
    }

    public function setVaultLayout(string $vaultLayout): VaultConfiguration
    {
        $this->vaultLayout = $vaultLayout;

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

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): VaultConfiguration
    {
        $this->priority = $priority;

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
        $array = get_object_vars($this);

        unset($array['configuration']);

        return $array;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new Assert\NotBlank());
        $metadata->addPropertyConstraint('adapter', new StoremanAssert\StorageAdapterExists());
        $metadata->addPropertyConstraint('conflictHandler', new StoremanAssert\ConflictHandlerExists());
        $metadata->addPropertyConstraint('indexMerger', new StoremanAssert\IndexMergerExists());
        $metadata->addPropertyConstraint('lockAdapter', new StoremanAssert\LockAdapterExists());
        $metadata->addPropertyConstraint('operationListBuilder', new StoremanAssert\OperationListBuilderExists());
    }
}
