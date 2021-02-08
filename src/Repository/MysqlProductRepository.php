<?php

namespace App\Repository;

use App\Entity\Product;
use App\Repository\Interfaces\ProductRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Elasticsearch\Client;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MysqlProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        protected LoggerInterface $logger,
        private Client $client
    ) {
        parent::__construct($registry, Product::class);
    }

    #[ArrayShape(['saved' => "bool", 'product.content' => "array|null"])]
    public function insertProduct(array $content): array
    {
        $saved = true;
        $entityManager = $this->getEntityManager();

        $product = new Product();
        $product->setContent($content);

        try {
            $entityManager->persist($product);
            $entityManager->flush();
        } catch (ORMException | OptimisticLockException  $e) {
            $saved = false;
            $this->logger->info($e->getMessage());
        }

        return ['saved' => $saved, 'product.content' => $product->getContent()];
    }

    #[ArrayShape(['saved' => "bool", 'product.content' => "array|null"])]
    public function findById(string $id): array
    {
        $dbResults = $this->find($id);
        if ($dbResults) {
            $dbResults = json_encode(get_object_vars($dbResults));
            return ['saved' => true, 'product.content' => "$dbResults"];
        }
        return ['saved' => false, 'product.content' => null];
    }
}
