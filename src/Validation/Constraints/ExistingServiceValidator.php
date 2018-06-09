<?php

namespace Storeman\Validation\Constraints;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ExistingServiceValidator extends ConstraintValidator
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
    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof ServiceExists))
        {
            throw new \LogicException();
        }

        if (!$this->container->has("{$constraint->getPrefix()}{$value}"))
        {
            $this->context->buildViolation($constraint->getMessage())
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
