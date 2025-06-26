<?php
// src/Validator/UniqueDefaultAddress.php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to ensure a Customer has at most one default billing
 * and one default shipping address.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueDefaultAddress extends Constraint
{
    public string $messageBilling = 'A customer cannot have more than one default billing address.';
    public string $messageShipping = 'A customer cannot have more than one default shipping address.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}