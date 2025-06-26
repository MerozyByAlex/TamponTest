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
use Doctrine\ORM\Mapping as ORM;

// Gedmo
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

// Symfony
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

// App
use App\Repository\CategoryRepository;

// Pour PreRemove, vous pourriez avoir besoin de LifecycleEventArgs si vous voulez plus de contexte
// use Doctrine\ORM\Event\LifecycleEventArgs;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['category:read', 'category:read:item']]),
        new GetCollection(normalizationContext: ['groups' => ['category:read', 'category:read:collection']]),
        new Post(
            denormalizationContext: ['groups' => ['category:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can create categories."
        ),
        new Put(
            denormalizationContext: ['groups' => ['category:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can update categories."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete categories."
        )
    ],
    normalizationContext: ['groups' => ['category:read']],
    denormalizationContext: ['groups' => ['category:write']],
    order: ['treeRoot' => 'ASC', 'lft' => 'ASC']
)]
#[Gedmo\Tree(type: 'nested')]
class Category
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['category:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category:read', 'category:write', 'product:read'])]
    #[Assert\NotBlank(message: "Category name cannot be blank.")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Category name must be at least {{ limit }} characters long.", maxMessage: "Category name cannot be longer than {{ limit }} characters.")]
    private ?string $name = null;

    #[Gedmo\Slug(fields: ['name'], unique: true, updatable: true)]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['category:read'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Groups(['category:read', 'category:write'])]
    private ?string $externalId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['category:read', 'category:write'])]
    #[Assert\Length(max: 255, maxMessage: "Meta title cannot be longer than {{ limit }} characters.")]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['category:read', 'category:write'])]
    #[Assert\Length(max: 1000, maxMessage: "Meta description cannot be longer than {{ limit }} characters.")]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['category:read', 'category:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['category:read', 'category:write'])]
    #[Assert\PositiveOrZero(message: "Position must be a positive number or zero.")]
    private int $position = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => true])]
    #[Groups(['category:read', 'category:write'])]
    private bool $isActive = true;

    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    #[Groups(['category:admin'])]
    private ?int $lft = null;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    #[Groups(['category:admin'])]
    private ?int $rgt = null;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    #[Groups(['category:admin'])]
    private ?int $lvl = null;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[Groups(['category:admin'])]
    private ?self $root = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subcategories')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups(['category:read', 'category:write'])]
    private ?self $parent = null;

    /** @var Collection<int, Category> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist'], orphanRemoval: false)]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    #[Groups(['category:read', 'category:read:item'])]
    private Collection $subcategories;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)] // Assurez-vous que l'entité Product est correctement mappée
    private Collection $products;

    public function __construct()
    {
        $this->subcategories = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    #[ORM\PreRemove]
    public function preventDeletionIfDependenciesExist(): void
    {
        if (!$this->subcategories->isEmpty()) {
            throw new \LogicException('Deletion Denied: This category cannot be deleted because it contains subcategories. Please reassign or delete subcategories first.');
        }

        if (!$this->products->isEmpty()) {
            throw new \LogicException('Deletion Denied: This category cannot be deleted because it has associated products. Please reassign or delete products first.');
        }
    }

    // --- Getters and Setters ---

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getSlug(): ?string { return $this->slug; }

    public function getExternalId(): ?string { return $this->externalId; }
    public function setExternalId(?string $externalId): static { $this->externalId = $externalId; return $this; }
    public function getMetaTitle(): ?string { return $this->metaTitle; }
    public function setMetaTitle(?string $metaTitle): static { $this->metaTitle = $metaTitle; return $this; }
    public function getMetaDescription(): ?string { return $this->metaDescription; }
    public function setMetaDescription(?string $metaDescription): static { $this->metaDescription = $metaDescription; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }

    /** @return Collection<int, Category> */
    public function getSubcategories(): Collection { return $this->subcategories; }
    public function addSubcategory(self $subcategory): static
    {
        if (!$this->subcategories->contains($subcategory)) {
            $this->subcategories[] = $subcategory;
            $subcategory->setParent($this);
        }
        return $this;
    }
    public function removeSubcategory(self $subcategory): static
    {
        if ($this->subcategories->removeElement($subcategory)) {
            if ($subcategory->getParent() === $this) {
                $subcategory->setParent(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection { return $this->products; }
    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setCategory($this);
        }
        return $this;
    }
    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }
        return $this;
    }

    // Les getters getCreatedAt() et getUpdatedAt() sont maintenant fournis par le Trait TimestampableEntity
    // (Le trait TimestampableEntity fournit également les annotations PHPDoc @return \DateTimeInterface|null pour ces méthodes)

    public function getLft(): ?int { return $this->lft; }
    public function getRgt(): ?int { return $this->rgt; }
    public function getLvl(): ?int { return $this->lvl; }
    public function getRoot(): ?self { return $this->root; }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}