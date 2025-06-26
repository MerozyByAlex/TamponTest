<?php

namespace App\Exception;

/**
 * Thrown when a VAT rate for a specific country code cannot be found.
 * This indicates a configuration or data integrity issue.
 */
class VatRateNotFoundException extends \LogicException
{
    public function __construct(string $countryCode, int $code = 0, \Throwable $previous = null)
    {
        $message = sprintf(
            'VAT rate for country code "%s" was not found in the database. Please check your vat_rates table configuration.',
            $countryCode
        );
        parent::__construct($message, $code, $previous);
    }
}