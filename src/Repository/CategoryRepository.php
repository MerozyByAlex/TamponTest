<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @extends NestedTreeRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends NestedTreeRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $entityClass = Category::class;
        $manager = $registry->getManagerForClass($entityClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(
                sprintf(
                    'Could not find the entity manager for class "%s". Expected an instance of "%s", got "%s".',
                    $entityClass,
                    EntityManagerInterface::class,
                    get_class($manager)
                )
            );
        }

        parent::__construct($manager, $manager->getClassMetadata($entityClass));
    }

    /**
     * Récupère les nœuds racines de l'arbre des catégories, ordonnés par leur position.
     *
     * @param string $direction ASC ou DESC
     * @return Category[]
     */
    public function findRootNodesWithPositionOrder(string $direction = 'ASC'): array
    {
        return $this->getRootNodesQueryBuilder()
            ->orderBy('node.position', $direction)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les enfants (directs ou non) d'une catégorie, triés par le champ souhaité.
     *
     * @param Category $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return Category[]
     */
    public function findChildrenWithPositionOrder(
        Category $node,
        bool $direct = true,
        ?string $sortByField = 'position',
        string $direction = 'ASC',
        bool $includeNode = false
    ): array {
        $qb = $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);

        if ($sortByField) {
            $qb->orderBy('node.' . $sortByField, $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les enfants directs actifs d'une catégorie, ordonnés par leur position.
     *
     * @param Category $parent
     * @param string $direction
     * @return Category[]
     */
    public function findActiveDirectChildrenOrderedByPosition(Category $parent, string $direction = 'ASC'): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parent')
            ->andWhere('c.isActive = :isActive')
            ->orderBy('c.position', $direction)
            ->setParameter('parent', $parent)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Variante basée sur getChildrenQueryBuilder de Gedmo pour récupérer les enfants actifs avec tri personnalisé.
     *
     * @param Category $node
     * @param string $sortByField
     * @param string $direction
     * @return Category[]
     */
    public function findActiveDirectChildrenGedmo(Category $node, string $sortByField = 'position', string $direction = 'ASC'): array
    {
        $qb = $this->getChildrenQueryBuilder($node, true, $sortByField, $direction);
        $alias = $qb->getRootAliases()[0];

        $qb->andWhere($alias . '.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy($alias . '.' . $sortByField, $direction);

        return $qb->getQuery()->getResult();
    }
}