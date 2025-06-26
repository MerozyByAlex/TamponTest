<?php
// src/Service/CustomerAddressManager.php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Customer;
use App\Enum\AddressTypeEnum;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Service responsible for managing the address logic for customers.
 * Ensures address consistency (e.g., only one default shipping/billing).
 */
class CustomerAddressManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository,
    ) {}

    /**
     * Ensures a customer has at most one default billing and one default shipping address.
     * Automatically resets other addresses if necessary.
     */
    public function enforceSingleDefault(Customer $customer, Address $newAddress): void
    {
        foreach ($customer->getAddresses() as $address) {
            if ($newAddress !== $address) {
                if ($newAddress->isDefaultBilling()) {
                    $address->setIsDefaultBilling(false);
                }
                if ($newAddress->isDefaultShipping()) {
                    $address->setIsDefaultShipping(false);
                }
            }
        }
    }

    /**
     * Assigns a unique external ID to a newly created address (e.g., for synchronization with external systems).
     */
    public function assignExternalId(Address $address): void
    {
        if (null === $address->getExternalId()) {
            $address->setExternalId(Uuid::v4()->toRfc4122());
        }
    }

    /**
     * Automatically assigns a new address as default if it's the customer's first one.
     */
    public function assignDefaultsIfFirst(Customer $customer, Address $address): void
    {
        if ($customer->getAddresses()->count() === 0) {
            $address->setIsDefaultBilling(true);
            $address->setIsDefaultShipping(true);
        }
    }

    /**
     * Checks whether the address is associated with the given customer.
     */
    public function isOwnedByCustomer(Customer $customer, Address $address): bool
    {
        return $address->getCustomer()?->getId() === $customer->getId();
    }
}