<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface; // Ajout de l'import pour LoggerInterface
use Psr\Log\LogLevel;      // Optionnel, pour utiliser les constantes de niveau de log

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private const SEARCHABLE_FIELDS = ['name', 'shortDescription', 'longDescription'];
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Product::class);
        $this->logger = $logger;
    }

    /**
     * Applies the visibility filter to the query builder.
     *
     * @param QueryBuilder $qb The QueryBuilder instance.
     * @param bool $onlyVisible If true, only visible products will be included.
     * @param string $alias The alias for the product entity in the query (default 'p').
     */
    private function applyVisibilityFilter(QueryBuilder $qb, bool $onlyVisible, string $alias = 'p'): void
    {
        if ($onlyVisible) {
            $qb->andWhere($alias . '.isVisible = :visible')
               ->setParameter('visible', true);
        }
    }

    /**
     * Returns products that are marked as visible (public catalog).
     *
     * @param int|null $limit
     * @param int|null $offset
     * @return Product[]
     */
    public function findVisibleProducts(?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isVisible = :visible')
            ->setParameter('visible', true)
            ->orderBy('p.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Searches for products by keyword in name, short or long description (case-insensitive).
     *
     * @param string $keyword
     * @param int|null $limit
     * @param int|null $offset
     * @param bool $onlyVisible Only search in visible products
     * @return Product[]
     */
    public function searchByKeyword(string $keyword, ?int $limit = null, ?int $offset = null, bool $onlyVisible = true): array
    {
        $this->logger->info('Product search initiated with keyword: "{keyword}"', ['keyword' => $keyword]);

        $qb = $this->createQueryBuilder('p');
        $orX = $qb->expr()->orX();

        foreach (self::SEARCHABLE_FIELDS as $field) {
            $orX->add($qb->expr()->like('LOWER(p.' . $field . ')', ':kw'));
        }
        $qb->where($orX)
           ->setParameter('kw', '%' . mb_strtolower($keyword) . '%');

        self::applyVisibilityFilter($qb, $onlyVisible);

        $qb->orderBy('p.name', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        $results = $qb->getQuery()->getResult();
        $resultCount = count($results);

        $this->logger->debug('Product search for keyword "{keyword}" found {count} results.', [
            'keyword' => $keyword,
            'count' => $resultCount
        ]);

        return $results;
    }

    /**
     * Returns a Doctrine Paginator for advanced pagination of visible products.
     *
     * @param int $page
     * @param int $pageSize
     * @return Paginator<Product>
     */
    public function getPaginatedVisibleProducts(int $page = 1, int $pageSize = 20): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isVisible = :visible')
            ->setParameter('visible', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return new Paginator($qb->getQuery());
    }

    /**
     * Finds a single product by its slug.
     *
     * @param string $slug
     * @param bool $onlyVisible Only find visible products
     * @return Product|null
     */
    public function findBySlug(string $slug, bool $onlyVisible = true): ?Product
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        self::applyVisibilityFilter($qb, $onlyVisible);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Counts all visible products.
     *
     * @return int
     */
    public function countVisible(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isVisible = :visible')
            ->setParameter('visible', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Finds the latest visible products, ordered by creation date.
     *
     * @param int $limit The maximum number of products to return.
     * @return Product[]
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isVisible = :visible')
            ->setParameter('visible', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds products belonging to a specific category.
     *
     * @param Category $category The category to filter by.
     * @param bool $onlyVisible If true, only visible products will be returned.
     * @param int|null $limit The maximum number of products to return.
     * @param string $orderByField The field to order by (e.g., 'name', 'createdAt').
     * @param string $orderDirection The order direction ('ASC' or 'DESC').
     * @return Product[]
     */
    public function findByCategory(
        Category $category,
        bool $onlyVisible = true,
        ?int $limit = null,
        string $orderByField = 'name',
        string $orderDirection = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->setParameter('category', $category)
            ->orderBy('p.' . $orderByField, $orderDirection);

        self::applyVisibilityFilter($qb, $onlyVisible);

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    // --- Future Considerations for Advanced Search ---

    /*
     * Future consideration: Implement a more advanced search method using a DTO.
     * This method would allow for complex filtering, sorting, and pagination
     * based on criteria defined in a dedicated SearchFilterDTO object.
     *
     * Example signature:
     * public function findBySearchFilters(ProductSearchCriteriaDTO $criteria): Paginator
     * {
     * $qb = $this->createQueryBuilder('p');
     *
     * // Apply filters from $criteria->getFilters()
     * // Apply sorting from $criteria->getSorting()
     * // Apply visibility rules
     * // ...
     *
     * // Handle pagination from $criteria->getPage(), $criteria->getPageSize()
     * $qb->setFirstResult(...)->setMaxResults(...);
     *
     * return new Paginator($qb->getQuery());
     * }
     */
}