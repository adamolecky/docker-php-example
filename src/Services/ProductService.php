<?php

namespace App\Services;

use App\Exceptions\ElasticOutOffOrderException;
use App\Exceptions\MySQLOutOffOrderException;
use App\Repository\ProductRepository;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductService
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var TagAwareCacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ProductRepository $productRepository,
        TagAwareCacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function handleDBConnections(string $id, array $indexDefinition)
    {
        return $this->cache->get($id, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            $dbResults = [];
            try {
                //TODO: {"data":["{placeholder : content3}"],"count":2}
//                $dbResults = $this->productRepository->elasticsearchFindById($id, $indexDefinition);
                //          } catch (ElasticOutOffOrderException $e) {
                //TODO: {"data":"[]","count":2}
                $dbResults = $this->productRepository->mysqlFindById($id);
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
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function setProductCount(string $id, ?int $productCount = null)
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

    public function handleSaveProduct()
    {
    }
}
