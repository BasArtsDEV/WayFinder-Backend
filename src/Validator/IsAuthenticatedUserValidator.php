<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class IsAuthenticatedUserValidator extends ConstraintValidator
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsAuthenticatedUser) {
            throw new UnexpectedTypeException($constraint, IsAuthenticatedUser::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof User) {
            throw new UnexpectedValueException($value, User::class);
        }

        $currentUser = $this->security->getUser();

        if (!$currentUser || $currentUser !== $value) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
