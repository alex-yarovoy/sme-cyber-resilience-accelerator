<?php

namespace App\Security\Validator;

use Symfony\Component\Validator\Constraint;

class PasswordStrength extends Constraint
{
    public string $message = 'Password must be at least 12 chars and include upper, lower, digit, and symbol.';
}


