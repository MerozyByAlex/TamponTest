<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter; // Ajout pour ApiFilter
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter; // Ajout pour SearchFilter
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
// use ApiPlatform\Metadata\ApiSubresource;
use App\Repository\ProductRepository;
use App\Enum\ProductConditionEnum; // Import de la nouvelle Enum
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read', 'product:read:detailed', 'timestampable', 'image:read', 'brand:read', 'category:read']],
    denormalizationContext: ['groups' => ['product:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['condition' => 'exact'])] // Ajout du filtre pour condition
class Product
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['product:read', 'product_variant:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write', 'product_variant:read'])]
    #[Assert\NotBlank(message: "Product name cannot be blank.")]
    #[Assert\Length(
        min: 2, max: 255,
        minMessage: "Product name must be at least {{ limit }} characters long.",
        maxMessage: "Product name cannot be longer than {{ limit }} characters."
    )]
    private ?string $name = null;

    #[Gedmo\Slug(fields: ['name'], unique: true, updatable: true)]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['product:read', 'product_variant:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    private ?string $longDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\Length(max: 255, maxMessage: "Meta title cannot be longer than {{ limit }} characters.")]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\Length(max: 1000, maxMessage: "Meta description cannot be longer than {{ limit }} characters.")]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\Type("string")]
    private ?string $keywords = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 2000])]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\NotBlank(message: "VAT rate cannot be empty.")]
    #[Assert\Type("integer")]
    #[Assert\Range(min: 0, max: 10000, notInRangeMessage: "VAT rate must be between {{ min }} and {{ max }}.")]
    private int $vatRate = 2000;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read', 'product:write', 'category:read'])]
    #[Assert\NotNull(message: "Product must be associated with a main category.")]
    private ?Category $category = null;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'product_extra_categories')]
    #[Groups(['product:read:detailed', 'product:write'])]
    private Collection $extraCategories;

    /** @var Collection<int, ProductVariant> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVariant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['product:read:detailed'])]
    #[Assert\Valid]
    private Collection $variants;

    /** @var Collection<int, ImageAsset> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ImageAsset::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    #[Groups(['product:read:detailed', 'image:read', 'product:write'])]
    #[Assert\Valid]
    // #[ApiProperty(subresource: new ApiSubresource())] // Commentez ou supprimez cette ligne pour l'instant
    private Collection $images;

    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'products', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['product:read', 'brand:read', 'product:write'])]
    private ?Brand $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\Type("string")]
    private ?string $manufacturerCode = null;

    /** @var Collection<int, Product> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'product_related_products',
        joinColumns: [new ORM\JoinColumn(name: 'product_source_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'product_target_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    #[Groups(['product:read:detailed', 'product:write'])]
    private Collection $relatedProducts;

    #[ORM\Column(type: 'string', length: 50, enumType: ProductConditionEnum::class, options: ['default' => ProductConditionEnum::NEW])]
    #[Groups(['product:read', 'product:write'])]
    #[Assert\NotNull(message: "Product condition cannot be null.")]
    private ProductConditionEnum $condition = ProductConditionEnum::NEW;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['product:read', 'product:write'])]
    private bool $isVisible = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['product:read', 'product:write'])]
    private bool $isFeatured = false;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    #[Assert\Type("string")]
    private ?string $baseReference = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read:detailed', 'product:write'])]
    #[Assert\Type("string")]
    private ?string $externalId = null;

    public function __construct()
    {
        $this->extraCategories = new ArrayCollection();
        $this->variants = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->relatedProducts = new ArrayCollection();
        $this->condition = ProductConditionEnum::NEW;
    }

    // --- Getters and Setters ---
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function getShortDescription(): ?string { return $this->shortDescription; }
    public function setShortDescription(?string $shortDescription): static { $this->shortDescription = $shortDescription; return $this; }
    public function getLongDescription(): ?string { return $this->longDescription; }
    public function setLongDescription(?string $longDescription): static { $this->longDescription = $longDescription; return $this; }
    public function getMetaTitle(): ?string { return $this->metaTitle; }
    public function setMetaTitle(?string $metaTitle): static { $this->metaTitle = $metaTitle; return $this; }
    public function getMetaDescription(): ?string { return $this->metaDescription; }
    public function setMetaDescription(?string $metaDescription): static { $this->metaDescription = $metaDescription; return $this; }
    public function getKeywords(): ?string { return $this->keywords; }
    public function setKeywords(?string $keywords): static { $this->keywords = $keywords; return $this; }
    public function getVatRate(): int { return $this->vatRate; }
    public function setVatRate(int $vatRate): static { $this->vatRate = $vatRate; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }
    public function getExtraCategories(): Collection { return $this->extraCategories; }
    public function addExtraCategory(Category $category): static { if (!$this->extraCategories->contains($category)) { $this->extraCategories[] = $category; } return $this; }
    public function removeExtraCategory(Category $category): static { $this->extraCategories->removeElement($category); return $this; }
    public function getVariants(): Collection { return $this->variants; }
    public function addVariant(ProductVariant $variant): static { if (!$this->variants->contains($variant)) { $this->variants[] = $variant; $variant->setProduct($this); } return $this; }
    public function removeVariant(ProductVariant $variant): static { if ($this->variants->removeElement($variant) && $variant->getProduct() === $this) { $variant->setProduct(null); } return $this; }
    public function getImages(): Collection { return $this->images; }
    public function addImage(ImageAsset $image): static { if (!$this->images->contains($image)) { $this->images[] = $image; $image->setProduct($this); } return $this; }
    public function removeImage(ImageAsset $image): static { if ($this->images->removeElement($image) && $image->getProduct() === $this) { $image->setProduct(null); } return $this; }
    #[Groups(['product:read'])]
    public function getMainImageUrl(): ?string { /** @var ImageAsset|false $firstImage */ $firstImage = $this->images->first(); return $firstImage && method_exists($firstImage, 'getFilePath') ? $firstImage->getFilePath() : null; }
    public function getBrand(): ?Brand { return $this->brand; }
    public function setBrand(?Brand $brand): static { $this->brand = $brand; return $this; }
    public function getManufacturerCode(): ?string { return $this->manufacturerCode; }
    public function setManufacturerCode(?string $manufacturerCode): static { $this->manufacturerCode = $manufacturerCode; return $this; }
    public function getRelatedProducts(): Collection { return $this->relatedProducts; }
    public function addRelatedProduct(Product $product): static { if (!$this->relatedProducts->contains($product)) { $this->relatedProducts[] = $product; } return $this; }
    public function removeRelatedProduct(Product $product): static { $this->relatedProducts->removeElement($product); return $this; }

    public function getCondition(): ProductConditionEnum
    {
        return $this->condition;
    }

    public function setCondition(ProductConditionEnum $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Returns a human-readable label for the product condition.
     */
    #[Groups(['product:read', 'product:read:detailed'])] // Exposer le libellé dans l'API
    public function getConditionLabel(): string
    {
        return $this->condition->getLabel(); // Appelle la méthode getLabel() de ProductConditionEnum
    }

    public function isVisible(): bool { return $this->isVisible; }
    public function setIsVisible(bool $isVisible): static { $this->isVisible = $isVisible; return $this; }
    public function isFeatured(): bool { return $this->isFeatured; }
    public function setIsFeatured(bool $isFeatured): static { $this->isFeatured = $isFeatured; return $this; }
    public function getBaseReference(): ?string { return $this->baseReference; }
    public function setBaseReference(?string $baseReference): static { $this->baseReference = $baseReference; return $this; }
    public function getExternalId(): ?string { return $this->externalId; }
    public function setExternalId(?string $externalId): static { $this->externalId = $externalId; return $this; }


    public function __toString(): string
    {
        return $this->name ?? 'Unnamed Product';
    }

    #[Groups(['timestampable'])] public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    #[Groups(['timestampable'])] public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}