<?php
// src/Entity/Customer.php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\CustomerTypeEnum;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customers')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
#[UniqueEntity(fields: ['siret'], message: 'This SIRET number is already used by another account.', ignoreNull: true)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['customer:read', 'customer:item:get', 'user:read', 'address:read']],
            security: "is_granted('ROLE_ADMIN') or object.getUserAccount() == user",
            securityMessage: "Access Denied: You can only view your own customer profile."
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['customer:read', 'user:read']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can list customer profiles."
        ),
        new Post(
            denormalizationContext: ['groups' => ['customer:write']],
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Customer profiles can only be created by administrators."
        ),
        new Put(
            denormalizationContext: ['groups' => ['customer:write']],
            security: "is_granted('ROLE_ADMIN') or object.getUserAccount() == user",
            securityMessage: "Access Denied: You can only update your own customer profile."
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Access Denied: Only administrators can delete customer profiles."
        )
    ],
    normalizationContext: ['groups' => ['customer:read', 'timestampable']],
    denormalizationContext: ['groups' => ['customer:write']],
    order: ['createdAt' => 'DESC']
)]
class Customer
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['customer:read'])]
    private ?int $id = null;

    // === Account Link ===

    #[ORM\OneToOne(inversedBy: 'customerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Assert\Valid]
    #[Groups(['customer:read', 'customer:write'])]
    private ?User $userAccount = null;

    #[ORM\Column(type: 'string', length: 50, enumType: CustomerTypeEnum::class)]
    #[Assert\NotBlank]
    #[Groups(['customer:read', 'customer:write'])]
    private ?CustomerTypeEnum $type = null;

    // === Identity ===

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $lastName = null;

    // === Company Info ===

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $companyForm = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $siret = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $vatNumber = null;

    // === Contact ===

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Groups(['customer:read', 'customer:write'])]
    private ?string $phoneNumber = null;

    // === Status ===

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['customer:read', 'admin:customer:write'])]
    private bool $isVerified = false;

    // === Relationships ===

    /**
     * @var Collection<int, Address>
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Groups(['customer:item:get'])]
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateDefaultAddresses(ExecutionContextInterface $context): void
    {
        $billing = 0;
        $shipping = 0;
        foreach ($this->addresses as $address) {
            if ($address->isDefaultBilling()) $billing++;
            if ($address->isDefaultShipping()) $shipping++;
        }
        if ($billing > 1) {
            $context->buildViolation('A customer cannot have more than one default billing address.')->atPath('addresses')->addViolation();
        }
        if ($shipping > 1) {
            $context->buildViolation('A customer cannot have more than one default shipping address.')->atPath('addresses')->addViolation();
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUserAccount(): ?User { return $this->userAccount; }
    public function setUserAccount(User $userAccount): static { $this->userAccount = $userAccount; return $this; }
    public function getType(): ?CustomerTypeEnum { return $this->type; }
    public function setType(CustomerTypeEnum $type): static { $this->type = $type; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }
    public function getCompanyName(): ?string { return $this->companyName; }
    public function setCompanyName(?string $companyName): static { $this->companyName = $companyName; return $this; }
    public function getCompanyForm(): ?string { return $this->companyForm; }
    public function setCompanyForm(?string $companyForm): static { $this->companyForm = $companyForm; return $this; }
    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): static { $this->siret = $siret; return $this; }
    public function getVatNumber(): ?string { return $this->vatNumber; }
    public function setVatNumber(?string $vatNumber): static { $this->vatNumber = $vatNumber; return $this; }
    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }
    public function getAddresses(): Collection { return $this->addresses; }
    public function addAddress(Address $address): static {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setCustomer($this);
        }
        return $this;
    }
    public function removeAddress(Address $address): static {
        if ($this->addresses->removeElement($address) && $address->getCustomer() === $this) {
            $address->setCustomer(null);
        }
        return $this;
    }

    public function isIndividual(): bool { return $this->type === CustomerTypeEnum::INDIVIDUAL; }
    public function isCompany(): bool { return $this->type === CustomerTypeEnum::COMPANY; }

    public function __toString(): string
    {
        if ($this->isCompany() && $this->companyName) return $this->companyName;
        if ($this->isIndividual() && $this->firstName && $this->lastName) return trim("{$this->firstName} {$this->lastName}");
        return $this->userAccount?->getEmail() ?? ('Customer #' . $this->id);
    }
}