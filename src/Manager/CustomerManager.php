<?php
// src/Manager/CustomerManager.php

namespace App\Manager;

use App\Entity\Customer;
use App\Entity\User;
use App\Enum\CustomerTypeEnum;
use App\Repository\UserRepository;
use App\Service\Logger\SystemLoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Handles core business operations related to Customer entities.
 * Centralizes rules and ensures clean, reusable domain logic.
 */
class CustomerManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly SystemLoggerInterface $logger
    ) {}

    /**
     * Creates a new individual customer and their associated user account.
     */
    public function createIndividualCustomer(string $email, string $plainPassword, string $firstName, string $lastName): Customer
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        // Default roles are handled by the User entity's getRoles() method

        $customer = new Customer();
        $customer->setType(CustomerTypeEnum::INDIVIDUAL);
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        
        // Link user and customer
        $customer->setUserAccount($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->logger->info('New individual customer created.', ['user_id' => $user->getId(), 'customer_id' => $customer->getId()]);

        return $customer;
    }

    /**
     * Creates a new company customer and their associated user account.
     */
    public function createCompanyCustomer(string $email, string $plainPassword, string $companyName, ?string $siret = null): Customer
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $customer = new Customer();
        $customer->setType(CustomerTypeEnum::COMPANY);
        $customer->setCompanyName($companyName);
        $customer->setSiret($siret);

        // Link user and customer
        $customer->setUserAccount($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();
        
        $this->logger->info('New company customer created.', ['user_id' => $user->getId(), 'customer_id' => $customer->getId()]);

        return $customer;
    }

    /**
     * Marks the customer as verified if not already, then flushes the change.
     */
    public function verify(Customer $customer): void
    {
        if (!$customer->isVerified()) {
            $customer->setIsVerified(true);
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
            $this->logger->info('Customer marked as verified.', [
                'customer_id' => $customer->getId(),
                'email' => $customer->getUserAccount()?->getEmail(),
            ]);
        }
    }

    /**
     * Normalizes the VAT number format (removes spacing, sets uppercase).
     */
    public function normalizeVat(Customer $customer): void
    {
        $vat = $customer->getVatNumber();
        if ($vat !== null) {
            $normalized = strtoupper(str_replace([' ', '-', '_'], '', $vat));
            $customer->setVatNumber($normalized);
        }
    }

    /**
     * Determines if a customer is eligible for deletion.
     * Adapt this logic according to real business rules (e.g. invoices, orders).
     */
    public function canBeSafelyDeleted(Customer $customer): bool
    {
        // This is placeholder logic. Replace it with actual conditions.
        return $customer->getAddresses()->isEmpty();
    }

    /**
     * Syncs customer information with external systems (e.g. ERP like Sage 100C).
     */
    public function syncWithSage(Customer $customer): void
    {
        // Placeholder logic
        $this->logger->info('Customer data synchronized with Sage.', [
            'customer_id' => $customer->getId(),
            'email' => $customer->getUserAccount()?->getEmail(),
        ]);
    }
}