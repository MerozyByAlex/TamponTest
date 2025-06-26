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
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
// Symfony
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
// App
use App\Repository\VariantOptionTypeRepository;

#[ORM\Entity(repositoryClass: VariantOptionTypeRepository::class)]
#[ORM\Table(name: 'variant_option_types', indexes: [ // Modification ici pour ajouter l'index
    new ORM\Index(name: 'idx_vot_position', columns: ['position'])
])]
#[UniqueEntity(fields: ['name'], message: 'This option type name already exists.')]
#[UniqueEntity(fields: ['code'], message: 'This technical code already exists.', ignoreNull: true)]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['option_type:read', 'option_type:item:read', 'timestampable']]),
        new GetCollection(normalizationContext: ['groups' => ['option_type:read', 'timestampable']]),
        new Post(
            denormalizationContext: ['groups' => ['option_type:write', 'admin:option_type:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can create option types."
        ),
        new Put(
            denormalizationContext: ['groups' => ['option_type:write', 'admin:option_type:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can update option types."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete option types."
        )
    ],
    normalizationContext: ['groups' => ['option_type:read', 'timestampable']],
    denormalizationContext: ['groups' => ['option_type:write']],
    order: ['position' => 'ASC', 'name' => 'ASC']
)]
class VariantOptionType
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['option_type:read', 'option_value:read', 'product_variant:read', 'product:read:detailed', 'admin:option_type:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['option_type:read', 'option_type:write', 'option_value:read', 'product_variant:read', 'product:read:detailed', 'admin:option_type:read'])]
    #[Assert\NotBlank(message: "Option type name cannot be blank.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Option type name must be at least {{ limit }} characters long.",
        maxMessage: "Option type name cannot be longer than {{ limit }} characters."
    )]
    private ?string $name = null;

    #[Gedmo\Slug(fields: ['name'], unique: true, updatable: false)]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['option_type:read', 'option_value:read', 'product_variant:read', 'product:read:detailed', 'admin:option_type:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['option_type:read', 'option_type:write', 'admin:option_type:read'])]
    #[Assert\PositiveOrZero(message: "Position must be a positive number or zero.")]
    private int $position = 0;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    #[Groups(['admin:option_type:read', 'admin:option_type:write'])]
    #[Assert\Length(max: 100, maxMessage: "Technical code cannot be longer than {{ limit }} characters.")]
    private ?string $code = null;

    /**
     * @var Collection<int, VariantOptionValue>
     */
    #[ORM\OneToMany(mappedBy: 'optionType', targetEntity: VariantOptionValue::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['option_type:item:read'])]
    #[ORM\OrderBy(['position' => 'ASC', 'value' => 'ASC'])]
    private Collection $variantOptionValues;

    public function __construct()
    {
        $this->variantOptionValues = new ArrayCollection();
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
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

    /**
     * @return Collection<int, VariantOptionValue>
     */
    public function getVariantOptionValues(): Collection
    {
        return $this->variantOptionValues;
    }

    public function addVariantOptionValue(VariantOptionValue $variantOptionValue): static
    {
        if (!$this->variantOptionValues->contains($variantOptionValue)) {
            $this->variantOptionValues->add($variantOptionValue);
            $variantOptionValue->setOptionType($this);
        }
        return $this;
    }

    public function removeVariantOptionValue(VariantOptionValue $variantOptionValue): static
    {
        if ($this->variantOptionValues->removeElement($variantOptionValue)) {
            // set the owning side to null (unless already changed)
            if ($variantOptionValue->getOptionType() === $this) {
                $variantOptionValue->setOptionType(null);
            }
        }
        return $this;
    }

    /**
     * Checks if a specific VariantOptionValue is associated with this VariantOptionType.
     *
     * @param VariantOptionValue $value
     * @return bool
     */
    public function hasVariantOptionValue(VariantOptionValue $value): bool
    {
        return $this->variantOptionValues->contains($value);
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}