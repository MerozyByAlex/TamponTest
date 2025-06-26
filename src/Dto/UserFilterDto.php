<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserFilterDto
{
    public ?bool $isActive = null;
    public ?bool $isVerified = null;

    #[Assert\Length(max: 255)]
    public ?string $emailContains = null;

    #[Assert\Length(max: 255)]
    public ?string $usernameContains = null;

    public ?string $role = null; // Should match a value from RoleEnum ideally
    public bool $excludeRole = false; // New property to handle role exclusion

    public ?bool $isSystemAccount = null;

    public ?\DateTimeImmutable $createdAfter = null;
    public ?\DateTimeImmutable $createdBefore = null;

    #[Assert\Type("string")]
    // Validated against ALLOWED_ORDER_FIELDS in UserRepository
    public ?string $orderBy = 'email'; // Default order field

    #[Assert\Choice(choices: ['ASC', 'DESC'], message: 'Order direction must be ASC or DESC.')]
    public ?string $orderDirection = 'ASC'; // Default order direction

    public function __construct(
        ?bool $isActive = null,
        ?bool $isVerified = null,
        ?string $emailContains = null,
        ?string $usernameContains = null,
        ?string $role = null,
        bool $excludeRole = false, // Added excludeRole
        ?bool $isSystemAccount = null,
        ?\DateTimeImmutable $createdAfter = null,
        ?\DateTimeImmutable $createdBefore = null,
        ?string $orderBy = 'email',
        ?string $orderDirection = 'ASC'
    ) {
        $this->isActive = $isActive;
        $this->isVerified = $isVerified;
        $this->emailContains = $emailContains;
        $this->usernameContains = $usernameContains;
        $this->role = $role;
        $this->excludeRole = $excludeRole; // Initialize excludeRole
        $this->isSystemAccount = $isSystemAccount;
        $this->createdAfter = $createdAfter;
        $this->createdBefore = $createdBefore;
        $this->orderBy = $orderBy ?: 'email';
        $this->orderDirection = strtoupper($orderDirection ?: 'ASC');
    }
}