<?php

namespace Storeman\Validation\Constraints;

class StorageAdapterExists extends ServiceExists
{
    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return 'storageAdapter.';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return "{{ value }} is not a valid storage adapter.";
    }
}
