<?php

namespace App\Repository;

use App\Entity\ImageAsset;
use App\Entity\Product;
use App\Entity\ProductVariant; // Ajout de l'import pour ProductVariant
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<ImageAsset>
 *
 * @method ImageAsset|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImageAsset|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImageAsset[]    findAll()
 * @method ImageAsset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageAssetRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, ImageAsset::class);
        $this->logger = $logger;
    }

    /**
     * Finds a single ImageAsset by its external ID.
     */
    public function findOneByExternalId(string $externalId): ?ImageAsset
    {
        $this->logger->debug('Searching for ImageAsset by externalId: {externalId}', ['externalId' => $externalId]);
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * Finds images that are not currently associated with a Product or a ProductVariant.
     * Note: This is a basic check based on direct relations in ImageAsset.
     */
    public function findOrphanedImages(): array
    {
        $this->logger->debug('Searching for orphaned images (not linked to Product or ProductVariant).');
        $results = $this->createQueryBuilder('ia')
            ->andWhere('ia.product IS NULL')
            ->andWhere('ia.productVariant IS NULL')
            // TODO: Extend if other entities directly reference ImageAsset in a way not captured here
            // (e.g., if Brand.logo was a OneToOne with ImageAsset.logoForBrand mappedBy)
            ->orderBy('ia.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $this->logger->debug('Found {count} potentially orphaned images.', ['count' => count($results)]);
        return $results;
    }

    /**
     * Counts images that are not currently associated with a Product or a ProductVariant.
     */
    public function countOrphanedImages(): int
    {
        $this->logger->debug('Counting orphaned images.');
        $count = (int) $this->createQueryBuilder('ia')
            ->select('COUNT(ia.id)')
            ->andWhere('ia.product IS NULL')
            ->andWhere('ia.productVariant IS NULL')
            // TODO: Extend similarly to findOrphanedImages if needed
            ->getQuery()
            ->getSingleScalarResult();

        $this->logger->debug('{count} potentially orphaned images counted.', ['count' => $count]);
        return $count;
    }

    /**
     * Searches for images by metadata like altText, title, or originalFilename.
     */
    public function searchByMetadata(string $term, int $limit = 10): array
    {
        $this->logger->debug('Searching images by metadata with term: {term}', ['term' => $term]);
        $qb = $this->createQueryBuilder('ia');
        $searchTerm = '%' . mb_strtolower($term) . '%';

        $orX = $qb->expr()->orX(
            $qb->expr()->like('LOWER(ia.altText)', ':term'),
            $qb->expr()->like('LOWER(ia.title)', ':term'),
            $qb->expr()->like('LOWER(ia.originalFilename)', ':term')
        );

        $qb->where($orX)
           ->setParameter('term', $searchTerm)
           ->orderBy('ia.createdAt', 'DESC')
           ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $this->logger->debug('Image metadata search found {count} results.', ['count' => count($results)]);
        return $results;
    }

    /**
     * Finds all ImageAsset entities for a given Product, ordered by their sortOrder.
     */
    public function findOrderedByProduct(Product $product): array
    {
        $this->logger->debug('Fetching images for Product ID {productId}, ordered by sortOrder.', ['productId' => $product->getId() ?? 'N/A_ID']);
        return $this->createQueryBuilder('ia')
            ->andWhere('ia.product = :product')
            ->setParameter('product', $product)
            ->orderBy('ia.sortOrder', 'ASC')
            ->addOrderBy('ia.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all ImageAsset entities for a given ProductVariant, ordered by their sortOrder.
     */
    public function findOrderedByProductVariant(ProductVariant $productVariant): array
    {
        $this->logger->debug('Fetching images for ProductVariant ID {variantId}, ordered by sortOrder.', ['variantId' => $productVariant->getId() ?? 'N/A_ID']);
        return $this->createQueryBuilder('ia')
            ->andWhere('ia.productVariant = :productVariant')
            ->setParameter('productVariant', $productVariant)
            ->orderBy('ia.sortOrder', 'ASC')
            ->addOrderBy('ia.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds the primary ImageAsset for a given Product.
     */
    public function findPrimaryForProduct(Product $product): ?ImageAsset
    {
        $this->logger->debug('Fetching primary image for Product ID {productId}.', ['productId' => $product->getId() ?? 'N/A_ID']);
        return $this->createQueryBuilder('ia')
            ->andWhere('ia.product = :product')
            ->andWhere('ia.isPrimary = true')
            ->setParameter('product', $product)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds the primary ImageAsset for a given ProductVariant.
     */
    public function findPrimaryForProductVariant(ProductVariant $productVariant): ?ImageAsset
    {
        $this->logger->debug('Fetching primary image for ProductVariant ID {variantId}.', ['variantId' => $productVariant->getId() ?? 'N/A_ID']);
        return $this->createQueryBuilder('ia')
            ->andWhere('ia.productVariant = :productVariant')
            ->andWhere('ia.isPrimary = true')
            ->setParameter('productVariant', $productVariant)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds recently uploaded images.
     *
     * @param int $limit
     * @return ImageAsset[]
     */
    public function findRecentlyUploaded(int $limit = 10): array
    {
        $this->logger->debug('Fetching {limit} recently uploaded images.', ['limit' => $limit]);
        $results = $this->createQueryBuilder('ia')
            ->orderBy('ia.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $this->logger->debug('Found {count} recently uploaded images.', ['count' => count($results)]);
        return $results;
    }

     /**
     * Finds ImageAsset entities that are missing an altText.
     * Useful for SEO and accessibility audits.
     *
     * @return ImageAsset[]
     */
    public function findImagesMissingAltText(): array
    {
        $this->logger->debug('Searching for images missing altText.');
        $results = $this->createQueryBuilder('ia')
            ->where('ia.altText IS NULL OR ia.altText = :emptyString')
            ->setParameter('emptyString', '')
            ->orderBy('ia.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $this->logger->debug('Found {count} images missing altText.', ['count' => count($results)]);
        return $results;
    }
}