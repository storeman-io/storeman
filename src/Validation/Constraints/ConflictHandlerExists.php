<?php

namespace Storeman\Validation\Constraints;

class ConflictHandlerExists extends ServiceExists
{
    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return 'conflictHandler.';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return "{{ value }} is not a valid conflict handler.";
    }
}
