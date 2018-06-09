<?php

namespace Storeman\Validation\Constraints;

class LockAdapterExists extends ServiceExists
{
    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return 'lockAdapter.';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return "{{ value }} is not a valid lock adapter.";
    }
}
