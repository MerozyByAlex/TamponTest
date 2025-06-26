<?php

namespace App\Repository;

use App\Entity\VariantOptionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<VariantOptionType>
 *
 * @method VariantOptionType|null find($id, $lockMode = null, $lockVersion = null)
 * @method VariantOptionType|null findOneBy(array $criteria, array $orderBy = null)
 * @method VariantOptionType[]    findAll()
 * @method VariantOptionType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariantOptionTypeRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, VariantOptionType::class);
        $this->logger = $logger;
    }

    // --- Accès direct / Finders spécifiques ---

    /**
     * Finds a single VariantOptionType by its slug.
     *
     * @param string $slug The slug to search for.
     * @return VariantOptionType|null
     */
    public function findBySlug(string $slug): ?VariantOptionType
    {
        return $this->createQueryBuilder('vot')
            ->andWhere('vot.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds a single VariantOptionType by its unique technical code.
     *
     * @param string $code The technical code to search for.
     * @return VariantOptionType|null
     */
    public function findOneByCode(string $code): ?VariantOptionType
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Returns all VariantOptionType objects ordered by their position, then by name.
     *
     * @return VariantOptionType[]
     */
    public function findAllOrderedByPosition(): array
    {
        return $this->createQueryBuilder('vot')
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vot.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // --- Chargement avec relations (Eager loading) ---

    /**
     * Returns all option types with their associated option values (eager loading).
     * Useful for admin interfaces or for preparing complex filter data.
     * Note: Assumes VariantOptionValue will have a 'position' and 'value' field for secondary sorting.
     *
     * @return VariantOptionType[]
     */
    public function findAllWithValues(): array
    {
        return $this->createQueryBuilder('vot')
            ->leftJoin('vot.variantOptionValues', 'vov')
            ->addSelect('vov')
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vov.position', 'ASC') // Assumes vov.position exists
            ->addOrderBy('vov.value', 'ASC')    // Assumes vov.value exists
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a paginated list of option types with their associated option values (eager loading).
     *
     * @param int $page
     * @param int $limit
     * @return Paginator<VariantOptionType>
     */
    public function findPaginatedWithValues(int $page = 1, int $limit = 20): Paginator
    {
        $qb = $this->createQueryBuilder('vot')
            ->leftJoin('vot.variantOptionValues', 'vov')
            ->addSelect('vov')
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vov.position', 'ASC') // Assumes vov.position exists
            ->addOrderBy('vov.value', 'ASC')    // Assumes vov.value exists
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery());
    }

    // --- Recherches textuelles / Autocomplétion ---

    /**
     * Searches for VariantOptionType by name (case-insensitive).
     *
     * @param string $name The partial name to search for.
     * @param int $limit Maximum number of results to return.
     * @return VariantOptionType[]
     */
    public function searchByName(string $name, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('vot');
        $this->applyCaseInsensitiveLike($qb, 'vot', 'name', 'searchTerm', $name, true); // true for first condition

        return $qb->orderBy('vot.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a list of option types formatted for autocomplete selectors (e.g., Select2).
     *
     * @param string $term The search term.
     * @param int $limit Maximum number of results.
     * @return array<int, array{id: int, text: string}>
     */
    public function getAutocompleteResults(string $term, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('vot')
            ->select('vot.id', 'vot.name AS text');

        $this->applyCaseInsensitiveLike($qb, 'vot', 'name', 'searchTerm', $term, true); // true for first condition

        return $qb->orderBy('vot.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    // --- Logique métier / Métriques / Listes spécifiques ---

    /**
     * Finds VariantOptionTypes that are actively used by at least one ProductVariant.
     * This method assumes that the 'VariantOptionValue' entity has a 'productVariants' association.
     *
     * @return VariantOptionType[]
     */
    public function findUsedTypes(): array
    {
        $this->logger->debug('Executing findUsedTypes query.'); // Utilisation directe du logger
        $startTime = microtime(true);

        $results = $this->createQueryBuilder('vot')
            ->innerJoin('vot.variantOptionValues', 'vov')
            ->innerJoin('vov.productVariants', 'pv')
            ->distinct()
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vot.name', 'ASC')
            ->getQuery()
            ->getResult();

        $duration = microtime(true) - $startTime;
        $this->logger->debug('findUsedTypes query executed in {duration} ms, found {count} types.', [
            'duration' => round($duration * 1000, 2),
            'count' => count($results)
            // Si besoin d'un ID spécifique ici, il faudrait le passer en paramètre à la méthode findUsedTypes
            // ou logger un contexte plus générique.
        ]);

        return $results;
    }

    /**
     * Counts all VariantOptionType entities.
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('vot')
            ->select('COUNT(vot.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a minimal list of option types (ID and name only), ordered by position then name.
     * Useful for populating dropdowns or for lightweight API responses.
     *
     * @return array<int, array{id: int, name: string}> The result is an array of arrays.
     */
    public function getMinimalList(): array
    {
        return $this->createQueryBuilder('vot')
            ->select('vot.id', 'vot.name')
            ->orderBy('vot.position', 'ASC')
            ->addOrderBy('vot.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    // --- Méthodes privées (Helpers) ---

    /**
     * Applies a case-insensitive LIKE condition to a QueryBuilder.
     * Can be used as the first condition or an additional one.
     *
     * @param QueryBuilder $qb The QueryBuilder instance.
     * @param string $alias The entity alias.
     * @param string $field The field to apply the LIKE condition on.
     * @param string $parameterName The name of the parameter for the LIKE value.
     * @param string $value The value to search for (without wildcards).
     * @param bool $isFirstCondition If true, uses 'where', otherwise 'andWhere'.
     */
    private function applyCaseInsensitiveLike(
        QueryBuilder $qb,
        string $alias,
        string $field,
        string $parameterName,
        string $value,
        bool $isFirstCondition = false
    ): void {
        $condition = $qb->expr()->like('LOWER(' . $alias . '.' . $field . ')', ':' . $parameterName);
        if ($isFirstCondition) {
            $qb->where($condition);
        } else {
            $qb->andWhere($condition);
        }
        $qb->setParameter($parameterName, '%' . mb_strtolower($value) . '%');
    }
}