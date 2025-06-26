<?php

namespace App\Service;

use App\Dto\Price\CalculatedPrice;
use App\Entity\Address;
use App\Entity\ProductVariant;
use App\Exception\VatRateNotFoundException;
use App\Repository\VatRateRepository;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class PriceCalculatorService
{
    private const DEFAULT_COUNTRY_CODE = 'FR'; // Your default country

    public function __construct(private readonly VatRateRepository $vatRateRepository)
    {
    }

    /**
     * Calculates a full price breakdown for a given variant and shipping address.
     *
     * @param ProductVariant $variant The product variant to price.
     * @param Address|null $shippingAddress The customer's shipping address to determine the country.
     * @return CalculatedPrice A DTO containing the full price breakdown (HT, TTC, VAT amount, rate).
     * @throws VatRateNotFoundException
     */
    public function calculatePriceBreakdown(ProductVariant $variant, ?Address $shippingAddress): CalculatedPrice
    {
        $basePriceHT = Money::ofMinor($variant->getEffectiveBasePriceExclTaxAndPreEcoTax(), 'EUR');
        $ecoTaxHT = Money::ofMinor($variant->getEcoTaxHT(), 'EUR');
        $totalHT = $basePriceHT->plus($ecoTaxHT);

        $countryCode = $shippingAddress ? $shippingAddress->getCountry() : self::DEFAULT_COUNTRY_CODE;
        
        $vatRateEntity = $this->vatRateRepository->findOrFail($countryCode);
        
        $vatRateDecimal = $vatRateEntity->getRateAsDecimal();

        $vatAmount = $totalHT->multipliedBy($vatRateDecimal, RoundingMode::HALF_UP);
        $totalTTC = $totalHT->plus($vatAmount);

        return new CalculatedPrice(
            $totalHT,
            $vatAmount,
            $totalTTC,
            $vatRateEntity->getRate(),
            $countryCode
        );
    }

    /**
     * A lightweight alias to get only the final tax-inclusive price.
     * Useful when the full breakdown is not needed.
     *
     * @param ProductVariant $variant The product variant to price.
     * @param Address|null $shippingAddress The customer's shipping address.
     * @return Money The final TTC price as a Money object.
     * @throws VatRateNotFoundException
     */
    public function calculateTtcOnly(ProductVariant $variant, ?Address $shippingAddress): Money
    {
        return $this->calculatePriceBreakdown($variant, $shippingAddress)->priceTTC;
    }
}