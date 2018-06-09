<?php

namespace Storeman\Validation\Constraints;

use Symfony\Component\Validator\Constraint;

abstract class ServiceExists extends Constraint
{
    /**
     * Has to return a string prefix which in conjunction with the validated value has to exist as a service name.
     *
     * @return string
     */
    abstract public function getPrefix(): string;

    /**
     * @return string
     */
    abstract public function getMessage(): string;

    /**
     * {@inheritdoc}
     */
    final public function validatedBy()
    {
        return ExistingServiceValidator::class;
    }
}
