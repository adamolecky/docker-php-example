<?php

namespace App\Repository;

use App\Entity\Product;
use App\Repository\Interfaces\ProductRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Elasticsearch\Client;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;

class ElasticProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        protected LoggerInterface $logger,
        private Client $client,
        private array $indexDefinition
    ) {
        parent::__construct($registry, Product::class);
    }

    #[ArrayShape(['saved' => "bool", 'product.content' => "array|null"])]
    public function findById(string $id): array
    {
        $result = $this?->client?->search(
            array_merge(
                $this->indexDefinition,
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

    /**
     * Parse csv and feed the data to Elasticsearch.
     * @param array $content
     * @return array
     */
    #[ArrayShape(['saved' => "bool", 'product.content' => "string"])]
    public function insertProduct(array $content): array
    {
        $result = $this?->client?->search(
            array_merge(
                $this->indexDefinition,
                ['body' => [
                    "size" => 1,
                    "sort" => [
                        [
                            "_id" => [
                            "order" => "desc"
                          ]
                        ]
                      ]
                ]]
            ));

        //TODO: this could be done on level of configuration of elastic.
        $lastId = (int)$result['hits']['hits'][0]['sort'][0];

        $doc = array_merge(
            $this->indexDefinition,
            [
                'id' => $lastId+1,
                'body' => [
                    'content' => (string)$content['data']
                ]
            ]
        );

        $response = $this->client->index($doc);
        return ['saved' => (bool)$response['_shards']['successful'], 'product.content' => (string)$content['data']];
    }
}
