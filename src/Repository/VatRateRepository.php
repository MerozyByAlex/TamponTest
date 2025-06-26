<?php

namespace App\Repository;

use App\Entity\VatRate;
use App\Exception\VatRateNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VatRate>
 *
 * @method VatRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method VatRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method VatRate[]    findAll()
 * @method VatRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VatRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VatRate::class);
    }

    /**
     * Finds a VatRate by its country code or throws a dedicated exception if not found.
     *
     * @param string $countryCode The two-letter country code (e.g., 'FR').
     * @return VatRate
     * @throws VatRateNotFoundException
     */
    public function findOrFail(string $countryCode): VatRate
    {
        $vatRate = $this->find($countryCode);

        if (null === $vatRate) {
            throw new VatRateNotFoundException($countryCode);
        }

        return $vatRate;
    }

    /**
     * Finds all VAT rates, ordered by country code.
     * Useful for a sorted display in an admin interface.
     *
     * @return VatRate[]
     */
    public function findAllOrderedByCountryCode(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.countryCode', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}