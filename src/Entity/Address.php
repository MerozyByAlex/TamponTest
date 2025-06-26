<?php
// src/Entity/Address.php

namespace App\Entity;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection, Post, Put, Delete};
use App\Enum\AddressTypeEnum;
use App\Repository\AddressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'addresses')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['address:read']],
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUserAccount() == user",
            securityMessage: 'Access denied.'
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['address:read']],
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUserAccount() == user",
            securityMessage: 'Access denied.'
        ),
        new Post(
            denormalizationContext: ['groups' => ['address:write']],
            security: "is_granted('ROLE_USER')",
            securityMessage: 'Access denied.'
        ),
        new Put(
            denormalizationContext: ['groups' => ['address:write']],
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUserAccount() == user",
            securityMessage: 'Access denied.'
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUserAccount() == user",
            securityMessage: 'Access denied.'
        )
    ],
    normalizationContext: ['groups' => ['address:read', 'timestampable']],
    denormalizationContext: ['groups' => ['address:write']],
    order: ['createdAt' => 'DESC']
)]
class Address
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['address:read', 'customer:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups(['address:read', 'address:write'])]
    private ?Customer $customer = null;

    #[ORM\Column(type: 'string', enumType: AddressTypeEnum::class, length: 20)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write'])]
    private ?AddressTypeEnum $type = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['address:read', 'address:write'])]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write'])]
    private string $street;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write'])]
    private string $postalCode;

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write'])]
    private string $city;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Groups(['address:read', 'address:write'])]
    private ?string $state = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Groups(['address:read', 'address:write'])]
    private ?string $country = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['address:read', 'address:write'])]
    private bool $isDefaultBilling = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['address:read', 'address:write'])]
    private bool $isDefaultShipping = false;

    public function getId(): ?int { return $this->id; }
    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): static { $this->customer = $customer; return $this; }
    public function getType(): ?AddressTypeEnum { return $this->type; }
    public function setType(AddressTypeEnum $type): static { $this->type = $type; return $this; }
    public function getCompanyName(): ?string { return $this->companyName; }
    public function setCompanyName(?string $companyName): static { $this->companyName = $companyName; return $this; }
    public function getStreet(): string { return $this->street; }
    public function setStreet(string $street): static { $this->street = $street; return $this; }
    public function getPostalCode(): string { return $this->postalCode; }
    public function setPostalCode(string $postalCode): static { $this->postalCode = $postalCode; return $this; }
    public function getCity(): string { return $this->city; }
    public function setCity(string $city): static { $this->city = $city; return $this; }
    public function getState(): ?string { return $this->state; }
    public function setState(?string $state): static { $this->state = $state; return $this; }
    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): static { $this->country = $country; return $this; }
    public function isDefaultBilling(): bool { return $this->isDefaultBilling; }
    public function setIsDefaultBilling(bool $isDefaultBilling): static { $this->isDefaultBilling = $isDefaultBilling; return $this; }
    public function isDefaultShipping(): bool { return $this->isDefaultShipping; }
    public function setIsDefaultShipping(bool $isDefaultShipping): static { $this->isDefaultShipping = $isDefaultShipping; return $this; }

    public function __toString(): string
    {
        return sprintf('%s, %s %s', $this->street, $this->postalCode, $this->city);
    }
}
