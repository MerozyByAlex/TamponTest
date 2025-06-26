<?php

namespace App\Dto\Price;

use Brick\Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * A DTO (Data Transfer Object) to hold the full breakdown of a calculated price.
 * This object is returned by the API to provide detailed price information to the frontend.
 */
class CalculatedPrice
{
    /**
     * @param Money $priceHT The total price excluding VAT.
     * @param Money $vatAmount The calculated amount of VAT.
     * @param Money $priceTTC The total price including VAT.
     * @param int $vatRate The VAT rate used for calculation, in basis points (e.g., 2000 for 20%).
     * @param string $countryCode The country code used for VAT calculation.
     */
    public function __construct(
        #[Groups(['price:read'])]
        public readonly Money $priceHT,

        #[Groups(['price:read'])]
        public readonly Money $vatAmount,

        #[Groups(['price:read'])]
        public readonly Money $priceTTC,

        #[Groups(['price:read'])]
        public readonly int $vatRate,
        
        #[Groups(['price:read'])]
        public readonly string $countryCode
    ) {
    }

    /**
     * Helper method to get the VAT rate as a display-friendly percentage.
     * Example: returns 20.0 for a vatRate of 2000.
     */
    public function getVatRatePercent(): float
    {
        return $this->vatRate / 100.0;
    }

    /**
     * Converts the DTO to a simple array.
     * Useful for logging, debugging, or contexts where the Serializer is not used.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'priceHT' => [
                'amount' => (string) $this->priceHT->getMinorAmount()->toInt(),
                'currency' => $this->priceHT->getCurrency()->getCurrencyCode(),
            ],
            'vatAmount' => [
                'amount' => (string) $this->vatAmount->getMinorAmount()->toInt(),
                'currency' => $this->vatAmount->getCurrency()->getCurrencyCode(),
            ],
            'priceTTC' => [
                'amount' => (string) $this->priceTTC->getMinorAmount()->toInt(),
                'currency' => $this->priceTTC->getCurrency()->getCurrencyCode(),
            ],
            'vatRate' => $this->vatRate,
            'vatRatePercent' => $this->getVatRatePercent(),
            'countryCode' => $this->countryCode,
        ];
    }
}