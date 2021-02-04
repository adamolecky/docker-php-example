<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Elasticsearch\Client;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        Client $client
    ) {
        parent::__construct($registry, Product::class);
        $this->logger = $logger;
        $this->client = $client;
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
        } catch (ORMException | OptimisticLockException  $e) {
            $saved = false;
            $this->logger->info($e->getMessage());
        }

        return ['saved' => $saved, 'product.content' => $product->getContent()];
    }

    public function mysqlFindById(string $id): string
    {
        $dbResults = $this->find($id);
        if ($dbResults) {
            return json_encode(get_object_vars($dbResults));
        }

        return '';
    }

    public function elasticsearchFindById(string $id, array $indexDefinition): string
    {
        $result = $this->client->search(
            array_merge(
                $indexDefinition,
                ['body' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'term' => [
                                    '_id' => ['value' => "$id"],
                                ],
                            ],
                        ],
                    ],
                ]]
            ));

        return array_map(function ($item) {
            return $item['_source']['content'];
        }, $result['hits']['hits']);
    }
}
