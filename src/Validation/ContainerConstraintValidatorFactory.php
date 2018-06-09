<?php

namespace Storeman\Validation;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Allows dependency injection for constraint validators.
 */
class ContainerConstraintValidatorFactory extends ConstraintValidatorFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(Constraint $constraint)
    {
        if ($this->container->has($constraint->validatedBy()))
        {
            return $this->container->get($constraint->validatedBy());
        }

        return parent::getInstance($constraint);
    }
}
