<?php

namespace Storeman\Validation\Constraints;

class OperationListBuilderExists extends ServiceExists
{
    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return 'operationListBuilder.';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return "{{ value }} is not a valid operation list builder.";
    }
}
