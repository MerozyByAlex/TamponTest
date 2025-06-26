<?php

namespace App\Entity;

// API Platform
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
use Doctrine\ORM\Mapping as ORM; // Assurez-vous que cet import est bien là
// Gedmo
// use Gedmo\Mapping\Annotation as Gedmo; // Décommenter si slug est activé
use Gedmo\Timestampable\Traits\TimestampableEntity;
// Symfony
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
// App
use App\Repository\VariantOptionValueRepository;

#[ORM\Entity(repositoryClass: VariantOptionValueRepository::class)]
#[ORM\Table(name: 'variant_option_values', indexes: [ // On ajoute la clé 'indexes' ici
    new ORM\Index(name: 'idx_vov_option_type_visible', columns: ['option_type_id', 'is_visible'])
])]
#[UniqueEntity(
    fields: ['optionType', 'value'],
    message: 'This value already exists for this option type.'
)]
#[UniqueEntity(fields: ['code'], message: 'This technical code already exists.', ignoreNull: true)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['option_value:read', 'option_type:read', 'timestampable']]),
        new GetCollection(normalizationContext: ['groups' => ['option_value:read', 'option_type:read', 'timestampable']]),
        new Post(
            denormalizationContext: ['groups' => ['option_value:write', 'admin:option_value:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can create option values."
        ),
        new Put(
            denormalizationContext: ['groups' => ['option_value:write', 'admin:option_value:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can update option values."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete option values."
        )
    ],
    normalizationContext: ['groups' => ['option_value:read', 'timestampable']],
    denormalizationContext: ['groups' => ['option_value:write']],
    order: ['optionType.name' => 'ASC', 'position' => 'ASC', 'value' => 'ASC']
)]
class VariantOptionValue
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['option_value:read', 'option_type:item:read', 'product_variant:read', 'product:read:detailed'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: VariantOptionType::class, inversedBy: 'variantOptionValues')]
    #[ORM\JoinColumn(name: 'option_type_id', nullable: false)] // 'name' explicite pour la colonne FK, correspond à l'index
    #[Groups(['option_value:read', 'option_value:write', 'product_variant:read', 'product:read:detailed'])]
    #[Assert\NotNull(message: "Option value must be linked to an option type.")]
    private ?VariantOptionType $optionType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['option_value:read', 'option_value:write', 'product_variant:read', 'product:read:detailed'])]
    #[Assert\NotBlank(message: "Option value cannot be blank.")]
    #[Assert\Length(max: 255, maxMessage: "Option value cannot be longer than {{ limit }} characters.")]
    private ?string $value = null;

    /*
    // Slug optionnel pour la valeur. Si activé, décommenter aussi l'import Gedmo.
    // Assurer l'unicité par rapport à son type d'option.
    #[Gedmo\Slug(fields: ['value'], updatable: false, uniqueOverObjects: ['optionType'])]
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['option_value:read', 'product_variant:read'])]
    private ?string $slug = null;
    */

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['option_value:read', 'option_value:write', 'product_variant:read', 'product:read:detailed'])]
    #[Assert\PositiveOrZero(message: "Position must be a positive number or zero.")]
    private int $position = 0;

    #[ORM\Column(length: 7, nullable: true)]
    #[Groups(['option_value:read', 'option_value:write', 'product_variant:read', 'product:read:detailed'])]
    #[Assert\Regex(
        pattern: "/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/",
        message: "This is not a valid hex color code (e.g., #FF0000 or #F00).",
        match: true,
        groups: null
    )]
    private ?string $colorCode = null;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    #[Groups(['admin:option_value:read', 'admin:option_value:write'])]
    #[Assert\Length(max: 100, maxMessage: "Technical code cannot be longer than {{ limit }} characters.")]
    private ?string $code = null;

    #[ORM\Column(name: 'is_visible', type: Types::BOOLEAN, options: ['default' => true])] // 'name' explicite pour la colonne, correspond à l'index
    #[Groups(['option_value:read', 'admin:option_value:write', 'product_variant:read', 'product:read:detailed'])]
    private bool $isVisible = true;

    /**
     * @var Collection<int, ProductVariant>
     */
    #[ORM\ManyToMany(targetEntity: ProductVariant::class, mappedBy: 'options')]
    private Collection $productVariants;

    public function __construct()
    {
        $this->productVariants = new ArrayCollection();
        $this->isVisible = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOptionType(): ?VariantOptionType
    {
        return $this->optionType;
    }

    public function setOptionType(?VariantOptionType $optionType): static
    {
        $this->optionType = $optionType;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /*
    public function getSlug(): ?string
    {
        return $this->slug;
    }
    */

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getColorCode(): ?string
    {
        return $this->colorCode;
    }

    public function setColorCode(?string $colorCode): static
    {
        $this->colorCode = $colorCode;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function getProductVariants(): Collection
    {
        return $this->productVariants;
    }

    /**
     * Provides a user-friendly label for display purposes, typically in UIs.
     * Format: "OptionTypeName: Value"
     */
    public function getLabel(): string
    {
        return sprintf('%s: %s', $this->optionType?->getName() ?? 'N/A Type', (string) $this->value);
    }

    /**
     * Default string representation of the option value.
     * Format: "Value (OptionTypeName)"
     */
    public function __toString(): string
    {
        return sprintf('%s (%s)', (string) $this->value, $this->optionType?->getName() ?? 'N/A Type');
    }
}