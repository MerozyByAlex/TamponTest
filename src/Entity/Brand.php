<?php

namespace App\Entity;

// API Platform
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
// Doctrine
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// Gedmo
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
// Symfony
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
// App
use App\Repository\BrandRepository;
// N'oubliez pas d'importer ImageAsset si ce n'est pas déjà fait par votre IDE
// use App\Entity\ImageAsset; // Sera nécessaire une fois ImageAsset.php créé

#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ORM\Table(name: 'brands')]
#[UniqueEntity(fields: ['name'], message: 'This brand name already exists.')]
#[UniqueEntity(fields: ['slug'], message: 'This brand slug already exists.')]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['brand:read', 'brand:item:read', 'image:read', 'timestampable']]), // Ajout de image:read
        new GetCollection(normalizationContext: ['groups' => ['brand:read', 'image:read', 'timestampable']]), // Ajout de image:read
        new Post(
            denormalizationContext: ['groups' => ['brand:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can create brands."
        ),
        new Put(
            denormalizationContext: ['groups' => ['brand:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can update brands."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete brands."
        )
    ],
    normalizationContext: ['groups' => ['brand:read', 'timestampable']],
    denormalizationContext: ['groups' => ['brand:write']],
    order: ['position' => 'ASC', 'name' => 'ASC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'website' => 'partial',
    'slug' => 'partial'
])]
#[ApiFilter(BooleanFilter::class, properties: ['isActive', 'isFeatured'])]
#[ApiFilter(OrderFilter::class, properties: ['name', 'position', 'isActive', 'isFeatured'], arguments: ['orderParameterName' => 'order'])]
class Brand
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['brand:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['brand:read', 'brand:write', 'product:read'])]
    #[Assert\NotBlank(message: "Brand name cannot be blank.")]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $name = null;

    #[Gedmo\Slug(fields: ['name'], unique: true, updatable: false)]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['brand:read', 'product:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['brand:read', 'brand:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['brand:item:read', 'brand:write'])]
    private ?string $longDescription = null;

    #[ORM\ManyToOne(targetEntity: ImageAsset::class, cascade: ['persist'])] // MODIFICATION ICI
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] // onDelete SET NULL si l'image est supprimée
    #[Groups(['brand:read', 'brand:write'])] // Exposer l'image (ou son IRI) et permettre de l'associer
    #[Assert\Valid] // Valider l'objet ImageAsset si fourni
    private ?ImageAsset $logo = null; // MODIFICATION ICI (anciennement logoPath)

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['brand:read', 'brand:write'])]
    #[Assert\Url(message: "The website URL '{{ value }}' is not a valid URL.")]
    private ?string $website = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['brand:read', 'brand:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['brand:read', 'brand:write'])]
    private bool $isFeatured = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['brand:item:read', 'brand:write'])]
    #[Assert\Length(max: 255, maxMessage: "Meta title cannot be longer than {{ limit }} characters.")]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['brand:item:read', 'brand:write'])]
    #[Assert\Length(max: 1000, maxMessage: "Meta description cannot be longer than {{ limit }} characters.")]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['brand:item:read', 'brand:write'])]
    private ?string $keywords = null; // Reste un string pour l'instant

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['brand:read', 'brand:write'])]
    #[Assert\PositiveOrZero(message: "Position must be a positive number or zero.")]
    private int $position = 0;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'brand', targetEntity: Product::class)]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->isActive = true;
        $this->isFeatured = false;
        $this->position = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(?string $longDescription): static
    {
        $this->longDescription = $longDescription;
        return $this;
    }

    public function getLogo(): ?ImageAsset // MODIFICATION ICI
    {
        return $this->logo;
    }

    public function setLogo(?ImageAsset $logo): static // MODIFICATION ICI
    {
        $this->logo = $logo;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    #[Groups(['brand:item:read'])]
    public function getVisibleProductsCount(): int
    {
        return $this->products->filter(fn(Product $product) => $product->isVisible())->count();
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}