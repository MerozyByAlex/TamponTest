<?php

namespace App\Entity;

use App\Repository\VatRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VatRateRepository::class)]
#[ORM\Table(name: 'vat_rates')]
class VatRate
{
    #[ORM\Id]
    #[ORM\Column(length: 2)]
    #[Assert\Country]
    private ?string $countryCode = null; // e.g., 'FR', 'DE', 'ES'

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $rate = null; // Stored in basis points. e.g., 2000 for 20.00%

    // --- Getters and Setters ---

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = strtoupper($countryCode);
        return $this;
    }

    public function getRate(): ?int
    {
        return $this->rate;
    }
    
    /**
     * Returns the rate as a decimal for calculations.
     * Example: for 2000 (20%), returns 0.20
     */
    public function getRateAsDecimal(): float
    {
        return $this->rate / 10000;
    }

    public function setRate(int $rate): static
    {
        $this->rate = $rate;
        return $this;
    }
}