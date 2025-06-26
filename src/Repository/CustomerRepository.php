<?php
// src/Repository/CustomerRepository.php

namespace App\Repository;

use App\Entity\Customer;
use App\Enum\CustomerTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for handling advanced Customer entity queries.
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * Returns all customers that are currently verified.
     *
     * @return Customer[]
     */
    public function findVerified(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one customer by its SIRET number.
     */
    public function findOneBySiret(string $siret): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.siret = :siret')
            ->setParameter('siret', $siret)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get customers by type (individual or company).
     *
     * @return Customer[]
     */
    public function findByType(CustomerTypeEnum $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns customers who have no addresses associated.
     *
     * @return Customer[]
     */
    public function findWithoutAddress(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.addresses', 'a')
            ->andWhere('a.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns customers created in the last N days.
     *
     * @return Customer[]
     */
    public function findRecent(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-$days days");

        return $this->createQueryBuilder('c')
            ->andWhere('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a QueryBuilder for admin data grid filters.
     * Useful for paginated admin UIs or custom API filters.
     */
    public function getAdminListQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.userAccount', 'u')
            ->addSelect('u')
            ->orderBy('c.createdAt', 'DESC');
    }
}