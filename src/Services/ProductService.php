<?php

namespace App\Services;

use App\Enum\DbStatusEnum;
use App\Exceptions\MySQLOutOffOrderException;
use App\Repository\ElasticProductRepository;
use App\Repository\MysqlProductRepository;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductService
{
    public function __construct(
        protected MysqlProductRepository $mysqlProductRepository,
        protected ElasticProductRepository $elasticProductRepository,
        protected TagAwareCacheInterface $cache,
        protected LoggerInterface $logger,
        protected string $dbState
    ) {}

    /**
     * @param string $id
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function handleDBConnections(string $id): mixed
    {
        return $this->cache->get($id, function(ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            $dbResults = [];
            try {
                $dbResults = $this->elasticProductRepository->findById($id);
                $dbResults = $this->mysqlProductRepository->findById($id);
            } catch (MySQLOutOffOrderException $e) {
                $this->logger->critical('Could not get data from DBs. Elastic, nor Mysql is working! Check DB status.');
            }
            if (null === $dbResults) {
                $dbResults['error'] = 'No such key in db.';
            }

            $item->tag($id);

            return $dbResults;
        });
    }

    /**
     * @param string $id
     * @return int
     * @throws InvalidArgumentException
     */
    public function updateProductCounter(string $id): int
    {
        $productCount = $this->setProductCount($id);

        $this->cache->invalidateTags(["count_{$id}"]);
        $this->cache->delete("count_{$id}");

        return $this->setProductCount($id, $productCount);
    }

    /**
     * @param string $id
     * @param int|null $productCount
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function setProductCount(string $id, ?int $productCount = null): mixed
    {
        $productCount = $this->cache->get("count_{$id}", function (ItemInterface $item) use ($id, $productCount) {
            $item->expiresAfter(3600);
            $sum = intval($item->get()) + 1;
            if ($productCount) {
                $sum = intval($productCount) + 1;
            }

            $item->tag("count_{$id}");

            return $sum;
        });

        return $productCount;
    }

    #[ArrayShape(['saved' => "bool", 'product.content' => "\array|null"])]
    public function handleSaveProduct(array $content): array
    {
        $product = [];
        if ($this->dbState === DbStatusEnum::ELASTIC || $this->dbState === DbStatusEnum::BOTH) {
            $product = $this->elasticProductRepository->insertProduct($content);
        }
        if ($this->dbState === DbStatusEnum::MYSQL || $this->dbState === DbStatusEnum::BOTH) {
            $product = $this->mysqlProductRepository->insertProduct($content);
        }

        return $product;
    }
}
