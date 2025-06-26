<?php
// src/Validator/UniqueDefaultAddressValidator.php

namespace App\Validator;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueDefaultAddressValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueDefaultAddress) {
            throw new UnexpectedValueException($constraint, UniqueDefaultAddress::class);
        }

        if (!$value instanceof Customer) {
            // This validator is only meant to be used on Customer entities.
            // If used elsewhere, it should not produce an error.
            return;
        }

        $billingCount = 0;
        $shippingCount = 0;

        foreach ($value->getAddresses() as $address) {
            $billingCount += $address->isDefaultBilling() ? 1 : 0;
            $shippingCount += $address->isDefaultShipping() ? 1 : 0;
        }

        if ($billingCount > 1) {
            $this->context->buildViolation($constraint->messageBilling)
                ->atPath('addresses')
                ->addViolation();
        }

        if ($shippingCount > 1) {
            $this->context->buildViolation($constraint->messageShipping)
                ->atPath('addresses')
                ->addViolation();
        }
    }
}