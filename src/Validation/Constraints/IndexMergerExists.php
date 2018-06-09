<?php

namespace Storeman\Validation\Constraints;

class IndexMergerExists extends ServiceExists
{
    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return 'indexMerger.';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return "{{ value }} is not a valid index merger.";
    }
}
