<?php

namespace App\Tests\Service;

use App\Dto\Price\CalculatedPrice;
use App\Entity\Address;
use App\Entity\ProductVariant;
use App\Entity\VatRate;
use App\Exception\VatRateNotFoundException;
use App\Repository\VatRateRepository;
use App\Service\PriceCalculatorService;
use PHPUnit\Framework\TestCase;

class PriceCalculatorServiceTest extends TestCase
{
    private VatRateRepository $vatRateRepositoryMock;
    private PriceCalculatorService $priceCalculator;

    protected function setUp(): void
    {
        // 1. Arrange: Create a mock of the repository.
        // It will behave exactly as we tell it to for each test.
        $this->vatRateRepositoryMock = $this->createMock(VatRateRepository::class);

        // 2. Create an instance of the service we want to test, injecting the mock.
        $this->priceCalculator = new PriceCalculatorService($this->vatRateRepositoryMock);
    }

    public function testCalculatesPriceBreakdownWithValidVatRate(): void
    {
        // Arrange: Define the product variant we are testing with.
        $variant = new ProductVariant();
        $variant->setPriceExclTaxAndPreEcoTax(10000); // 100.00 EUR HT
        $variant->setEcoTaxHT(500); // 5.00 EUR HT

        // Arrange: Configure the mock to return a specific VAT rate when `findOrFail` is called with 'FR'.
        $frenchVat = new VatRate();
        $frenchVat->setCountryCode('FR')->setRate(2000); // 20%
        $this->vatRateRepositoryMock
            ->method('findOrFail')
            ->with('FR')
            ->willReturn($frenchVat);
            
        // 3. Act: Call the method we want to test.
        $result = $this->priceCalculator->calculatePriceBreakdown($variant, null); // null address uses default country 'FR'

        // 4. Assert: Check if the results are correct.
        $this->assertInstanceOf(CalculatedPrice::class, $result);
        // Total HT = 10000 + 500 = 10500
        $this->assertEquals('10500', $result->priceHT->getMinorAmount()->toInt());
        // VAT = 10500 * 0.20 = 2100
        $this->assertEquals('2100', $result->vatAmount->getMinorAmount()->toInt());
        // Total TTC = 10500 + 2100 = 12600
        $this->assertEquals('12600', $result->priceTTC->getMinorAmount()->toInt());
        $this->assertEquals(2000, $result->vatRate);
        $this->assertEquals('FR', $result->countryCode);
    }

    public function testThrowsExceptionWhenVatRateIsNotFound(): void
    {
        // Arrange: Configure the mock to throw our custom exception when called with 'XX'.
        $this->vatRateRepositoryMock
            ->method('findOrFail')
            ->with('XX')
            ->willThrowException(new VatRateNotFoundException('XX'));

        // Assert: We expect this specific exception to be thrown.
        $this->expectException(VatRateNotFoundException::class);
        $this->expectExceptionMessage('VAT rate for country code "XX" was not found');

        // Act: Call the method that should trigger the exception.
        $variant = new ProductVariant();
        $address = new Address();
        $address->setCountry('XX');
        $this->priceCalculator->calculatePriceBreakdown($variant, $address);
    }
}