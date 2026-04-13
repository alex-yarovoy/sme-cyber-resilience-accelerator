<?php

namespace App\Security\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordStrengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $ok = strlen($value) >= 12
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);

        if (!$ok) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}


