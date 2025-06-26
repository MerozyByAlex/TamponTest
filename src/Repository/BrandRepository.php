<?php

namespace App\Repository;

use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Brand>
 *
 * @method Brand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Brand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Brand[]    findAll()
 * @method Brand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrandRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Brand::class);
        $this->logger = $logger;
    }

    /**
     * Helper to get a consistent logging context for brand ID.
     */
    private function getBrandLogContext(Brand $brand): array
    {
        return ['brandId' => $brand->getId() ?? 'N/A_ID'];
    }

    /**
     * Finds all active brands, ordered by their position and then by name.
     *
     * @return Brand[]
     */
    public function findActiveBrandsOrdered(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('b.position', 'ASC')
            ->addOrderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds a single active Brand by its slug.
     *
     * @param string $slug The slug to search for.
     * @return Brand|null
     */
    public function findActiveBySlug(string $slug): ?Brand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug = :slug')
            ->andWhere('b.isActive = :isActive')
            ->setParameter('slug', $slug)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds a single active Brand by a partial match on its slug.
     *
     * @param string $term The partial slug term to search for.
     * @return Brand|null
     */
    public function findOneActiveByPartialSlug(string $term): ?Brand
    {
        $this->logger->debug('Searching for active brand by partial slug: {term}', ['term' => $term]);
        // Slugs are usually already lowercase and normalized by Gedmo.
        // A case-insensitive LIKE might still be safer depending on DB collation for slugs.
        // For simplicity, direct LIKE is used here; consider LOWER() if issues arise.
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug LIKE :term')
            ->andWhere('b.isActive = :isActive')
            ->setParameter('term', '%' . $term . '%')
            ->setParameter('isActive', true)
            ->orderBy('b.slug', 'ASC') // Prioritize shorter/exact matches if multiple partials exist
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * Finds all distinct active categories where products of a given brand are present.
     *
     * @param Brand $brand
     * @return Category[]
     */
    public function findCategoriesForBrand(Brand $brand): array
    {
        $this->logger->debug('Fetching categories for brand.', $this->getBrandLogContext($brand));

        $mainCategoriesQb = $this->_em->createQueryBuilder()
            ->select('DISTINCT c.id AS category_id')
            ->from('App\Entity\Product', 'p_main')
            ->innerJoin('p_main.category', 'c')
            ->where('p_main.brand = :brand')
            ->andWhere('p_main.isVisible = true')
            ->andWhere('c.isActive = true');
        $mainCategoryIdsData = $mainCategoriesQb->setParameter('brand', $brand)->getQuery()->getScalarResult();
        $mainCategoryIds = array_column($mainCategoryIdsData, 'category_id');

        $extraCategoriesQb = $this->_em->createQueryBuilder()
            ->select('DISTINCT ec.id AS category_id')
            ->from('App\Entity\Product', 'p_extra')
            ->innerJoin('p_extra.extraCategories', 'ec')
            ->where('p_extra.brand = :brand')
            ->andWhere('p_extra.isVisible = true')
            ->andWhere('ec.isActive = true');
        $extraCategoryIdsData = $extraCategoriesQb->setParameter('brand', $brand)->getQuery()->getScalarResult();
        $extraCategoryIds = array_column($extraCategoryIdsData, 'category_id');

        $allCategoryIds = array_unique(array_merge($mainCategoryIds, $extraCategoryIds));

        if (empty($allCategoryIds)) {
            $this->logger->debug('No active categories found for brand.', $this->getBrandLogContext($brand));
            return [];
        }

        $categories = $this->_em->getRepository(Category::class)->createQueryBuilder('cat')
            ->where('cat.id IN (:ids)')
            ->setParameter('ids', $allCategoryIds)
            ->orderBy('cat.treeRoot', 'ASC')
            ->addOrderBy('cat.lft', 'ASC')
            ->getQuery()
            ->getResult();

        $this->logger->debug('Found {count} active categories for brand.', array_merge(
            ['count' => count($categories)],
            $this->getBrandLogContext($brand)
        ));

        return $categories;
    }

    /**
     * Returns a list of active brands formatted for autocomplete selectors.
     * Ordered by name, then by ID for stable results.
     *
     * @param string $term The search term.
     * @param int $limit Maximum number of results.
     * @return array<int, array{id: int, text: string}>
     */
    public function getAutocompleteResults(string $term, int $limit = 10): array
    {
        $this->logger->debug('Brand autocomplete search with term "{term}"', ['term' => $term]);
        $qb = $this->createQueryBuilder('b')
            ->select('b.id', 'b.name AS text')
            ->andWhere('LOWER(b.name) LIKE :term')
            ->andWhere('b.isActive = true')
            ->setParameter('term', '%' . mb_strtolower($term) . '%')
            ->orderBy('b.name', 'ASC')
            ->addOrderBy('b.id', 'ASC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getArrayResult();
        $this->logger->debug('Brand autocomplete found {count} results.', ['count' => count($results)]);
        return $results;
    }

    /**
     * Counts the number of visible products associated with a given brand.
     *
     * @param Brand $brand
     * @return int
     */
    public function countVisibleProducts(Brand $brand): int
    {
        $this->logger->debug('Counting visible products for brand.', $this->getBrandLogContext($brand));

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(p.id)')
           ->from(Product::class, 'p')
           ->where('p.brand = :brand')
           ->andWhere('p.isVisible = :isVisible')
           ->setParameter('brand', $brand)
           ->setParameter('isVisible', true);

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        $this->logger->debug('{count} visible products found for brand.', array_merge(
            ['count' => $count],
            $this->getBrandLogContext($brand)
        ));
        return $count;
    }

    /**
     * Finds top brands by the number of visible products they have.
     * Can optionally be filtered by a category.
     *
     * @param int $limit The maximum number of brands to return.
     * @param Category|null $category An optional category to filter products by.
     * @return Brand[]
     */
    public function findTopBrandsByProductCount(int $limit = 10, ?Category $category = null): array
    {
        $logContext = ['limit' => $limit];
        if ($category) {
            $logContext['categoryId'] = $category->getId() ?? 'N/A_ID';
            $this->logger->debug('Finding top {limit} brands by product count in category ID {categoryId}.', $logContext);
        } else {
            $this->logger->debug('Finding top {limit} brands by product count (all categories).', $logContext);
        }

        $qb = $this->createQueryBuilder('b')
            ->select('b, COUNT(DISTINCT p.id) AS HIDDEN productCount') // Use DISTINCT p.id if products could be joined multiple ways
            ->join('b.products', 'p')
            ->andWhere('b.isActive = true')
            ->andWhere('p.isVisible = true');

        if ($category) {
            // This checks the main category of the product.
            // If you also need to check p.extraCategories, the query becomes more complex (e.g. another join or subquery)
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        $qb->groupBy('b.id, b.name') // Group by all selected non-aggregated fields of b
            ->orderBy('productCount', 'DESC')
            ->addOrderBy('b.name', 'ASC')
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        $logContext['foundCount'] = count($results);
        if ($category) {
            $this->logger->debug('Found {foundCount} top brands in category.', $logContext);
        } else {
            $this->logger->debug('Found {foundCount} top brands overall.', $logContext);
        }
        return $results;
    }
}