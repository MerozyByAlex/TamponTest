<?php

namespace App\Repository;

use App\Entity\VariantOptionType;
use App\Entity\VariantOptionValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<VariantOptionValue>
 *
 * @method VariantOptionValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method VariantOptionValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method VariantOptionValue[]    findAll()
 * @method VariantOptionValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariantOptionValueRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, VariantOptionValue::class);
        $this->logger = $logger;
    }

    /**
     * Finds all visible VariantOptionValue entities for a given VariantOptionType,
     * ordered by their position and then by their value.
     *
     * @param VariantOptionType $optionType The parent option type.
     * @return VariantOptionValue[]
     */
    public function findVisibleByOptionType(VariantOptionType $optionType): array
    {
        return $this->createQueryBuilder('vov')
            ->andWhere('vov.optionType = :optionType')
            ->andWhere('vov.isVisible = :isVisible') // Utilisation de :isVisible
            ->setParameter('optionType', $optionType)
            ->setParameter('isVisible', true)       // Paramètre :isVisible
            ->orderBy('vov.position', 'ASC')
            ->addOrderBy('vov.value', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds a single VariantOptionValue by its string value and its parent VariantOptionType.
     *
     * @param VariantOptionType $optionType The parent option type.
     * @param string $valueString The string value to search for.
     * @param bool $onlyVisible Whether to only find visible values.
     * @return VariantOptionValue|null
     */
    public function findOneByOptionTypeAndValue(
        VariantOptionType $optionType,
        string $valueString,
        bool $onlyVisible = true
    ): ?VariantOptionValue {
        $qb = $this->createQueryBuilder('vov')
            ->andWhere('vov.optionType = :optionType')
            ->andWhere('vov.value = :valueString')
            ->setParameter('optionType', $optionType)
            ->setParameter('valueString', $valueString);

        if ($onlyVisible) {
            $qb->andWhere('vov.isVisible = :isVisible') // Utilisation de :isVisible
               ->setParameter('isVisible', true);       // Paramètre :isVisible
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds a single VariantOptionValue by its unique technical code.
     *
     * @param string $code The technical code to search for.
     * @return VariantOptionValue|null
     */
    public function findOneByCode(string $code): ?VariantOptionValue
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Returns a list of option values for a given type, formatted for autocomplete selectors.
     * Only returns visible values. Searches in 'value' and 'code' fields.
     *
     * @param VariantOptionType $optionType The parent option type.
     * @param string $term The search term.
     * @param int $limit Maximum number of results.
     * @return array<int, array{id: int, text: string}>
     */
    public function getAutocompleteResultsByOptionType(VariantOptionType $optionType, string $term, int $limit = 10): array
    {
        $this->logger->debug('Autocomplete search for VariantOptionValue by type "{optionTypeName}" with term "{term}".', [
            'optionTypeName' => $optionType->getName(),
            'term' => $term,
            'limit' => $limit,
        ]);

        $qb = $this->createQueryBuilder('vov')
            ->select('vov.id', 'vov.value AS text')
            ->andWhere('vov.optionType = :optionType')
            ->andWhere('vov.isVisible = :isVisible'); // Utilisation de :isVisible

        $searchTerm = '%' . mb_strtolower($term) . '%';
        $orX = $qb->expr()->orX();
        $orX->add($qb->expr()->like('LOWER(vov.value)', ':term'));
        $orX->add($qb->expr()->like('LOWER(vov.code)', ':term'));
        $qb->andWhere($orX);

        $qb->setParameter('optionType', $optionType)
            ->setParameter('term', $searchTerm)
            ->setParameter('isVisible', true)       // Paramètre :isVisible
            ->orderBy('vov.position', 'ASC')
            ->addOrderBy('vov.value', 'ASC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getArrayResult();
        $this->logger->debug('Autocomplete search for VariantOptionValue by type found {count} results.', ['count' => count($results)]);
        return $results;
    }

    /**
     * Finds all VariantOptionValue entities, ordered by their parent OptionType's position and name,
     * then by their own position and value.
     *
     * @param bool $onlyVisible Whether to only include visible values.
     * @return VariantOptionValue[]
     */
    public function findAllOrderedByTypeAndValue(bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('vov')
            ->innerJoin('vov.optionType', 'vot')
            ->addSelect('vot')
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vot.name', 'ASC')
            ->addOrderBy('vov.position', 'ASC')
            ->addOrderBy('vov.value', 'ASC');

        if ($onlyVisible) {
            $qb->andWhere('vov.isVisible = :isVisible') // Utilisation de :isVisible
               ->setParameter('isVisible', true);       // Paramètre :isVisible
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Performs a global search for visible VariantOptionValues by their 'value' or 'code' field.
     * Returns results formatted for autocomplete.
     *
     * @param string $term The search term.
     * @param int $limit Maximum number of results.
     * @return array<int, array{id: int, text: string}>
     */
    public function searchAll(string $term, int $limit = 10): array
    {
        $this->logger->debug('Global search for VariantOptionValue with term "{term}".', [
            'term' => $term,
            'limit' => $limit,
        ]);

        $qb = $this->createQueryBuilder('vov')
            ->select('vov.id', 'vov.value AS text');

        $searchTerm = '%' . mb_strtolower($term) . '%';
        $orX = $qb->expr()->orX();
        $orX->add($qb->expr()->like('LOWER(vov.value)', ':term'));
        $orX->add($qb->expr()->like('LOWER(vov.code)', ':term'));
        $qb->where($orX);

        $qb->andWhere('vov.isVisible = :isVisible') // Modification ici pour utiliser un paramètre
            ->setParameter('term', $searchTerm)
            ->setParameter('isVisible', true)       // Paramètre :isVisible
            ->orderBy('vov.value', 'ASC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getArrayResult();
        $this->logger->debug('Global search for VariantOptionValue found {count} results.', ['count' => count($results)]);
        return $results;
    }

    /**
     * Counts VariantOptionValue entities for a given VariantOptionType.
     *
     * @param VariantOptionType $optionType The parent option type.
     * @param bool $onlyVisible If true, counts only visible values. Defaults to false (counts all).
     * @return int
     */
    public function countByOptionType(VariantOptionType $optionType, bool $onlyVisible = false): int
    {
        $this->logger->debug('Counting VariantOptionValues for type "{optionTypeName}". Visible only: {onlyVisible}', [
            'optionTypeName' => $optionType->getName(),
            'onlyVisible' => $onlyVisible,
        ]);

        $qb = $this->createQueryBuilder('vov')
            ->select('COUNT(vov.id)')
            ->where('vov.optionType = :optionType')
            ->setParameter('optionType', $optionType);

        if ($onlyVisible) {
            $qb->andWhere('vov.isVisible = :isVisible') // Utilisation de :isVisible
               ->setParameter('isVisible', true);       // Paramètre :isVisible
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->logger->debug('Count for VariantOptionValues for type "{optionTypeName}" is {count}.', [
            'optionTypeName' => $optionType->getName(),
            'count' => $count,
        ]);
        return $count;
    }

    /**
     * Counts all VariantOptionValue entities.
     * Can optionally count only visible ones.
     *
     * @param bool $onlyVisible If true, counts only visible values.
     * @return int
     */
    public function countAll(bool $onlyVisible = false): int
    {
        $qb = $this->createQueryBuilder('vov')
            ->select('COUNT(vov.id)');

        if ($onlyVisible) {
            $qb->where('vov.isVisible = :isVisible') // Utilisation de :isVisible
               ->setParameter('isVisible', true);    // Paramètre :isVisible
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns a minimal list of option values (ID and value only) for a given option type,
     * ordered by position then value. Useful for populating dropdowns.
     *
     * @param VariantOptionType $optionType The parent option type.
     * @param bool $onlyVisible If true, only visible values will be returned (default true).
     * @return array<int, array{id: int, value: string}> The result is an array of arrays.
     */
    public function getMinimalList(VariantOptionType $optionType, bool $onlyVisible = true): array
    {
        $qb = $this->createQueryBuilder('vov')
            ->select('vov.id', 'vov.value')
            ->where('vov.optionType = :optionType')
            ->setParameter('optionType', $optionType)
            ->orderBy('vov.position', 'ASC')
            ->addOrderBy('vov.value', 'ASC');

        if ($onlyVisible) {
            $qb->andWhere('vov.isVisible = :isVisible') // Utilisation de :isVisible
               ->setParameter('isVisible', true);       // Paramètre :isVisible
        }

        return $qb->getQuery()->getArrayResult();
    }
}