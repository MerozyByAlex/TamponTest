<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProductVariantRepository;
use App\Enum\AvailabilityStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['product_variant:read', 'timestampable', 'image:read', 'option_value:read', 'product:read']],
    denormalizationContext: ['groups' => ['product_variant:write', 'sage_sync:write']]
)]
#[ORM\Table(name: 'product_variants')]
class ProductVariant
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['product_variant:read', 'product:read:detailed'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\NotNull(message: "Variant must be associated with a parent product.")]
    private ?Product $product = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\Type("string")]
    private ?string $sku = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\Type("string")]
    #[Assert\Regex(
        pattern: "/^(\d{8}|\d{12}|\d{13}|\d{14})?$/",
        message: "GTIN must be a string of 8, 12, 13, or 14 digits, or empty."
    )]
    private ?string $gtin = null;

    #[ORM\Column(type: Types::INTEGER, options: ["unsigned" => true, "default" => 0])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\NotNull(message: "Variant base price (excl. eco-tax, excl. VAT) cannot be empty.")]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero(message: "Variant base price (excl. eco-tax, excl. VAT) must be positive or zero.")]
    private int $priceExclTaxAndPreEcoTax = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ["unsigned" => true])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero(message: "Variant sale price (excl. eco-tax, excl. VAT) must be positive or zero.")]
    private ?int $salePriceExclTaxAndPreEcoTax = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    private bool $isOnSale = false;

    #[ORM\Column(type: Types::INTEGER, options: ["unsigned" => true, "default" => 0], name: "eco_tax_ht")]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\NotNull]
    #[Assert\Type("integer")]
    #[Assert\PositiveOrZero(message: "Eco-tax HT must be a positive amount or zero.")]
    private int $ecoTaxHT = 0;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 0])]
    #[Groups(['sage_sync:write', 'admin:product_variant:read'])]
    #[Assert\NotNull]
    #[Assert\Type("integer")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Stock cannot be negative.")]
    private int $stock = 0;

    #[ORM\Column(type: 'string', length: 50, enumType: AvailabilityStatus::class, options: ["default" => AvailabilityStatus::ON_ORDER])]
    #[Groups(['product_variant:read', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\NotNull]
    private AvailabilityStatus $availabilityStatus = AvailabilityStatus::ON_ORDER;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['product_variant:read', 'sage_sync:write', 'product:read:detailed'])]
    private bool $isPreorder = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['product_variant:read', 'sage_sync:write', 'admin:product_variant:read'])]
    private ?\DateTimeInterface $replenishmentDate = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ["unsigned" => true])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Type("float")]
    #[Assert\PositiveOrZero(message: "Weight must be positive or zero.")]
    private ?float $weight = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ["unsigned" => true])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Type("float")]
    #[Assert\PositiveOrZero(message: "Width must be positive or zero.")]
    private ?float $width = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ["unsigned" => true])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Type("float")]
    #[Assert\PositiveOrZero(message: "Height must be positive or zero.")]
    private ?float $height = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ["unsigned" => true])]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Type("float")]
    #[Assert\PositiveOrZero(message: "Depth must be positive or zero.")]
    private ?float $depth = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Type("string")]
    private ?string $externalIdVariant = null;

    /** @var Collection<int, VariantOptionValue> */
    #[ORM\ManyToMany(targetEntity: VariantOptionValue::class, inversedBy: 'productVariants', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'product_variant_options')]
    #[ORM\OrderBy(['id' => 'ASC'])] // Or by optionType.position, then option.position
    #[Groups(['product_variant:read', 'product_variant:write', 'sage_sync:write', 'product:read:detailed'])]
    #[Assert\Count(min: 1, minMessage: "A variant must be defined by at least one option.")]
    #[Assert\Valid]
    private Collection $options;

    /** @var Collection<int, ImageAsset> */
    #[ORM\OneToMany(mappedBy: 'productVariant', targetEntity: ImageAsset::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    #[Groups(['product_variant:read', 'image:read', 'product_variant:write', 'sage_sync:write'])]
    #[Assert\Valid]
    private Collection $images;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->priceExclTaxAndPreEcoTax = 0;
        $this->ecoTaxHT = 0;
        $this->isOnSale = false;
        $this->stock = 0;
        $this->availabilityStatus = AvailabilityStatus::ON_ORDER;
        $this->isPreorder = false;
    }

    // --- Getters and Setters ---
    // They remain identical, only a comment is translated for consistency.
    // Ensure they are all present.
    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }
    public function getSku(): ?string { return $this->sku; }
    public function setSku(?string $sku): static { $this->sku = $sku; return $this; }
    public function getGtin(): ?string { return $this->gtin; }
    public function setGtin(?string $gtin): static { $this->gtin = $gtin; return $this; }
    public function getPriceExclTaxAndPreEcoTax(): int { return $this->priceExclTaxAndPreEcoTax; }
    public function setPriceExclTaxAndPreEcoTax(int $price): static { $this->priceExclTaxAndPreEcoTax = $price; return $this; }
    public function getSalePriceExclTaxAndPreEcoTax(): ?int { return $this->salePriceExclTaxAndPreEcoTax; }
    public function setSalePriceExclTaxAndPreEcoTax(?int $price): static { $this->salePriceExclTaxAndPreEcoTax = $price; return $this; }
    public function isOnSale(): bool { return $this->isOnSale; }
    public function setIsOnSale(bool $isOnSale): static { $this->isOnSale = $isOnSale; return $this; }
    public function getEcoTaxHT(): int { return $this->ecoTaxHT; }
    public function setEcoTaxHT(int $ecoTaxHT): static { $this->ecoTaxHT = $ecoTaxHT; return $this; }
    public function getStock(): int { return $this->stock; }
    public function setStock(int $stock): static { $this->stock = $stock; return $this; }
    public function getAvailabilityStatus(): AvailabilityStatus { return $this->availabilityStatus; }
    public function setAvailabilityStatus(AvailabilityStatus $availabilityStatus): static { $this->availabilityStatus = $availabilityStatus; return $this; }
    public function isPreorder(): bool { return $this->isPreorder; }
    public function setIsPreorder(bool $isPreorder): static { $this->isPreorder = $isPreorder; return $this; }
    public function getReplenishmentDate(): ?\DateTimeInterface { return $this->replenishmentDate; }
    public function setReplenishmentDate(?\DateTimeInterface $replenishmentDate): static { $this->replenishmentDate = $replenishmentDate; return $this; }
    public function getWeight(): ?float { return $this->weight; }
    public function setWeight(?float $weight): static { $this->weight = $weight; return $this; }
    public function getWidth(): ?float { return $this->width; }
    public function setWidth(?float $width): static { $this->width = $width; return $this; }
    public function getHeight(): ?float { return $this->height; }
    public function setHeight(?float $height): static { $this->height = $height; return $this; }
    public function getDepth(): ?float { return $this->depth; }
    public function setDepth(?float $depth): static { $this->depth = $depth; return $this; }
    public function getExternalIdVariant(): ?string { return $this->externalIdVariant; }
    public function setExternalIdVariant(?string $externalIdVariant): static { $this->externalIdVariant = $externalIdVariant; return $this; }
    /** @return Collection<int, VariantOptionValue> */
    public function getOptions(): Collection { return $this->options; }
    public function addOption(VariantOptionValue $option): static { if (!$this->options->contains($option)) { $this->options[] = $option; } return $this; }
    public function removeOption(VariantOptionValue $option): static { $this->options->removeElement($option); return $this; }
    /** @return Collection<int, ImageAsset> */
    public function getImages(): Collection { return $this->images; }
    public function addImage(ImageAsset $image): static { if (!$this->images->contains($image)) { $this->images[] = $image; $image->setProductVariant($this); } return $this; }
    public function removeImage(ImageAsset $image): static { if ($this->images->removeElement($image) && $image->getProductVariant() === $this) { $image->setProductVariant(null); } return $this; }

    // --- Entity Business Logic ---

    #[Groups(['product_variant:read', 'product:read:detailed'])]
    public function getFullName(): string { return (string) $this; }

    /**
     * Gets the effective base price (tax exclusive), taking into account if the variant is on sale.
     * This is the price before adding the eco-tax.
     */
    #[Groups(['product_variant:read', 'product:read:detailed'])]
    public function getEffectiveBasePriceExclTaxAndPreEcoTax(): int
    {
        return ($this->isOnSale && $this->salePriceExclTaxAndPreEcoTax !== null && $this->salePriceExclTaxAndPreEcoTax >= 0)
            ? $this->salePriceExclTaxAndPreEcoTax
            : $this->priceExclTaxAndPreEcoTax;
    }

    /**
     * Gets the total price (tax exclusive), including the eco-tax.
     * This is the final tax-exclusive amount.
     */
    #[Groups(['product_variant:read', 'product:read:detailed'])]
    public function getTotalPriceHT(): int
    {
        return $this->getEffectiveBasePriceExclTaxAndPreEcoTax() + $this->getEcoTaxHT();
    }

    /**
     * NOTE: All tax-inclusive (TTC/VAT) calculation methods have been removed from this entity.
     * The final TTC price depends on the customer's shipping country and its specific VAT rate.
     * This logic is now handled by the `App\Service\PriceCalculatorService` to ensure
     * that the correct VAT rate is applied based on the context of the sale.
     */

    public function __toString(): string
    {
        $optionStrings = [];
        foreach ($this->options as $option) { $optionStrings[] = (string) $option; }
        $optionsDisplay = implode(' / ', $optionStrings);
        $productName = $this->product ? $this->product->getName() : 'N/A Product';
        $skuDisplayPart = $this->sku ? " (SKU: {$this->sku})" : "";
        if (!empty($optionsDisplay)) { return sprintf('%s - %s%s', $productName, $optionsDisplay, $skuDisplayPart); }
        return sprintf('%s%s', $productName, $skuDisplayPart);
    }
}