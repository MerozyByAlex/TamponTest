<?php
// src/Repository/AddressRepository.php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for managing address-related database queries.
 * Includes helpers to retrieve default billing and shipping addresses.
 *
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Finds the default billing address for a specific customer.
     */
    public function findDefaultBillingForCustomer(Customer $customer): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.customer = :customer')
            ->andWhere('a.isDefaultBilling = true')
            ->setParameter('customer', $customer)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds the default shipping address for a specific customer.
     */
    public function findDefaultShippingForCustomer(Customer $customer): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.customer = :customer')
            ->andWhere('a.isDefaultShipping = true')
            ->setParameter('customer', $customer)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns all addresses of a customer sorted by creation date descending.
     */
    public function findAllForCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}