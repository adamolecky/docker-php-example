<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * @var $logger LoggerInterface
     */
    protected $logger;
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger
    ){
        parent::__construct($registry, Product::class);
        $this->logger = $logger;
    }

    public function insertProduct(array $content): array
    {
        $saved = true;
        $entityManager = $this->getEntityManager();

        $product = new Product();
        $product->setContent($content);

        try {
            $entityManager->persist($product);
            $entityManager->flush();
        } catch (ORMException | OptimisticLockException  $e){
            $saved = false;
            $this->logger->info($e->getMessage());
        }

        return ['saved' => $saved, 'product.content' => $product->getContent()];
    }
}
