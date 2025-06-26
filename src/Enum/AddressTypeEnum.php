<?php
// src/Enum/AddressTypeEnum.php

namespace App\Enum;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Enum representing the types of addresses for a customer.
 * Typically used to distinguish between billing and shipping.
 */
enum AddressTypeEnum: string
{
    case BILLING = 'billing';
    case SHIPPING = 'shipping';

    /**
     * Returns an array of all enum values.
     * Useful for form types and validation constraints.
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Returns a human-readable label for each case.
     * You may localize this later for multilingual support.
     */
    public function label(): string
    {
        return match ($this) {
            self::BILLING => 'Billing address',
            self::SHIPPING => 'Shipping address',
        };
    }
}