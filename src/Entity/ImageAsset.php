<?php

namespace App\Entity;

// API Platform
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
// Doctrine
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// Gedmo
use Gedmo\Timestampable\Traits\TimestampableEntity;
// Symfony
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
// VichUploaderBundle
use Vich\UploaderBundle\Mapping\Annotation as Vich;
// App
use App\Repository\ImageAssetRepository;

#[ORM\Entity(repositoryClass: ImageAssetRepository::class)]
#[ORM\Table(name: 'image_assets')]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['image:read', 'admin:image:read', 'timestampable']]),
        new GetCollection(normalizationContext: ['groups' => ['image:read', 'admin:image:read', 'timestampable']]),
        new Post(
            security: "is_granted('ROLE_ADMIN')"
            // La section 'openapiContext' a été supprimée car elle causait une erreur.
            // La documentation OpenAPI pour l'upload de fichiers avec API Platform
            // est généralement gérée via un DTO spécifié dans 'input' et/ou des configurations plus avancées.
            // Pour l'instant, l'opération Post est simplifiée pour permettre la création de métadonnées.
            // L'upload de fichier lui-même sera géré par VichUploaderBundle, typiquement via un formulaire Symfony
            // ou un DataProcessor/Controller personnalisé pour l'API.
            // Le denormalizationContext global s'appliquera pour les champs autorisés.
        ),
        new Put(
            denormalizationContext: ['groups' => ['image:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can update image assets."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete image assets."
        )
    ],
    normalizationContext: ['groups' => ['image:read', 'timestampable']],
    denormalizationContext: ['groups' => ['image:write']], // Permet d'écrire 'altText', 'title', 'sortOrder', 'isPrimary', etc.
    order: ['sortOrder' => 'ASC', 'createdAt' => 'DESC']
)]
#[ApiFilter(SearchFilter::class, properties: ['altText' => 'partial', 'externalId' => 'exact', 'title' => 'partial'])]
class ImageAsset
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['image:read', 'admin:image:read', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'image_assets', fileNameProperty: 'filePath', size: 'filesize', mimeType: 'mimeType', originalName: 'originalFilename', dimensions: 'dimensions')]
    #[Assert\Image(
        maxSize: "5M",
        mimeTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        mimeTypesMessage: "Please upload a valid image (JPEG, PNG, GIF, WebP)."
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read', 'admin:image:read', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read', 'admin:image:read', 'image:write', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    #[Assert\Length(max: 255)]
    private ?string $altText = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read', 'admin:image:read', 'image:write', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read', 'admin:image:read'])]
    private ?string $originalFilename = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['image:read', 'admin:image:read'])]
    private ?int $filesize = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['image:read', 'admin:image:read'])]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['image:read', 'admin:image:read', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    private ?int $width = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups(['image:read', 'admin:image:read', 'product:read:detailed', 'product_variant:read', 'brand:read'])]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 0])]
    #[Groups(['image:read', 'admin:image:read', 'image:write'])]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    #[Groups(['image:read', 'admin:image:read', 'image:write'])]
    private bool $isPrimary = false;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Groups(['admin:image:read', 'admin:image:write'])]
    private ?string $externalId = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups(['image:read', 'image:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'product_variant_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups(['image:read', 'image:write'])]
    private ?ProductVariant $productVariant = null;

    /**
     * Utilisé par VichUploaderBundle pour stocker temporairement les dimensions.
     * @var array|null
     */
    private ?array $dimensions = null;


    public function __construct()
    {
        $this->isPrimary = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            // Nécessaire pour que l'événement update soit déclenché si seul le fichier change
             $this->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    public function setFilesize(?int $filesize): static
    {
        $this->filesize = $filesize;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getProductVariant(): ?ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariant $productVariant): static
    {
        $this->productVariant = $productVariant;
        return $this;
    }

    public function setDimensions(?array $dimensions): static
    {
        if ($dimensions) {
            $this->setWidth($dimensions[0] ?? null);
            $this->setHeight($dimensions[1] ?? null);
        }
        return $this;
    }

    public function getDimensions(): ?array
    {
        if ($this->width !== null && $this->height !== null) {
            return [$this->width, $this->height];
        }
        return null;
    }

    #[Groups(['admin:image:read'])]
    public function isOrphaned(): bool
    {
        return $this->getProduct() === null && $this->getProductVariant() === null;
    }

    public function __toString(): string
    {
        return (string) $this->filePath ?: ($this->originalFilename ?: (string) $this->id);
    }
}