<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Enum\AvailabilityStatus; // Conserver si utilisé, même si findAvailableVariantsByProduct se base sur stock > 0
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<ProductVariant>
 *
 * @method ProductVariant|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductVariant|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductVariant[]    findAll()
 * @method ProductVariant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductVariantRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, ProductVariant::class);
        $this->logger = $logger;
    }

    /**
     * Finds a single ProductVariant by its SKU.
     *
     * @param string $sku The SKU to search for.
     * @return ProductVariant|null
     */
    public function findOneBySku(string $sku): ?ProductVariant
    {
        $this->logger->debug('Searching for ProductVariant by SKU: {sku}', ['sku' => $sku]);
        return $this->findOneBy(['sku' => $sku]);
    }

    /**
     * Finds a single ProductVariant for a given Product and an exact set of VariantOptionValue IDs.
     *
     * @param Product $product The parent Product.
     * @param array<int> $optionValueIds An array of VariantOptionValue IDs that define the variant.
     * @return ProductVariant|null
     */
    public function findByProductAndExactOptionValues(Product $product, array $optionValueIds): ?ProductVariant
    {
        $this->logger->debug('Searching for ProductVariant for Product ID {productId} with exact option value IDs: {optionValueIds}', [
            'productId' => $product->getId() ?? 'N/A_ID', // Fallback pour l'ID
            'optionValueIds' => $optionValueIds,
        ]);

        if (empty($optionValueIds)) {
            $this->logger->debug('Empty optionValueIds provided, cannot find a variant. ProductVariant requires at least one option.');
            return null;
        }

        $countOfOptionIds = count($optionValueIds);

        $qb = $this->createQueryBuilder('pv')
            ->innerJoin('pv.options', 'o')
            ->andWhere('pv.product = :product')
            ->setParameter('product', $product)
            ->groupBy('pv.id')
            ->having('COUNT(DISTINCT o.id) = :count')
            ->andHaving('SUM(CASE WHEN o.id IN (:optionValueIds) THEN 1 ELSE 0 END) = :count')
            ->setParameter('optionValueIds', $optionValueIds)
            ->setParameter('count', $countOfOptionIds)
            ->setMaxResults(1);

        $variant = $qb->getQuery()->getOneOrNullResult();

        if ($variant) {
            $this->logger->debug('Found ProductVariant ID {variantId} for Product ID {productId} with exact options.', [
                'variantId' => $variant->getId(),
                'productId' => $product->getId() ?? 'N/A_ID', // Fallback pour l'ID
            ]);
        } else {
            $this->logger->debug('No ProductVariant found for Product ID {productId} with the specified exact options.', [
                'productId' => $product->getId() ?? 'N/A_ID', // Fallback pour l'ID
            ]);
        }

        return $variant;
    }

    /**
     * Finds available ProductVariants for a given Product.
     * "Available" is currently defined as having stock > 0.
     *
     * @param Product $product The parent Product.
     * @return ProductVariant[]
     */
    public function findAvailableVariantsByProduct(Product $product): array
    {
        $this->logger->debug('Fetching available variants for Product ID {productId}', ['productId' => $product->getId() ?? 'N/A_ID']); // Fallback

        $qb = $this->createQueryBuilder('pv')
            ->andWhere('pv.product = :product')
            ->andWhere('pv.stock > 0')
            ->setParameter('product', $product)
            ->orderBy('pv.id', 'ASC');

        $results = $qb->getQuery()->getResult();
        $this->logger->debug('Found {count} available variants for Product ID {productId}', [
            'count' => count($results),
            'productId' => $product->getId() ?? 'N/A_ID' // Fallback
        ]);
        return $results;
    }

    /**
     * Finds variants with stock below a given threshold but still in stock.
     * Useful for stock management dashboards or alerts.
     *
     * @param int $threshold The stock level threshold.
     * @return ProductVariant[]
     */
    public function findLowStockVariants(int $threshold = 5): array
    {
        $this->logger->debug('Searching for variants with stock under {threshold} and greater than 0.', ['threshold' => $threshold]);

        $results = $this->createQueryBuilder('pv')
            ->andWhere('pv.stock < :threshold')
            ->andWhere('pv.stock > 0') // Ensure it's not completely out of stock
            ->setParameter('threshold', $threshold)
            ->orderBy('pv.stock', 'ASC') // Order by stock level, lowest first
            ->getQuery()
            ->getResult();

        $this->logger->debug('Found {count} variants with low stock (under {threshold}, above 0).', [
            'count' => count($results),
            'threshold' => $threshold,
        ]);
        return $results;
    }
}