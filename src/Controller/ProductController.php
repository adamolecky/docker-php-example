<?php

namespace App\Controller;

use App\Drivers\Elastic\IElasticSearchDriver;
use App\Drivers\SQL\IMySQLDriver;
use App\Exceptions\ElasticOutOffOrderException;
use App\Exceptions\MySQLOutOffOrderException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    /**
     * @var $elasticDriver IElasticSearchDriver
     */
    protected $elasticDriver;

    /**
     * @var $sqlDriver IMySQLDriver
     */
    protected $sqlDriver;

    /**
     * @var $cache TagAwareCacheInterface
     */
    protected $cache;

    /**
     * @var $logger LoggerInterface
     */
    protected $logger;

    public function __construct(
        IElasticSearchDriver $elasticSearchDriver,
        IMySQLDriver $mySQLDriver,
        TagAwareCacheInterface $cache,
        LoggerInterface $logger
    ){
        $this->elasticDriver = $elasticSearchDriver;
        $this->sqlDriver = $mySQLDriver;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @Route("/{id}")
     * @param string $id
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function detail(string $id): JsonResponse
    {
        $response['data'] = $this->handleDBConnections($id);
        if(sizeof($response['data'])) {
            $response['count'] = $this->updateProductCounter($id);
        }

        return (new JsonResponse($response));
    }

    /**
     * @param string $id
     * @param int|null $productCount
     * @return mixed
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

    /**
     * @param string $id
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function handleDBConnections(string $id)
    {
        return $this->cache->get($id, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(3600);
            $dbResults = [];
            try {
                $dbResults = $this->elasticDriver->findById($id);
            } catch (ElasticOutOffOrderException $e) {
                try {
                    $dbResults = $this->sqlDriver->findProduct($id);
                } catch (MySQLOutOffOrderException $e) {
                    $this->logger->critical('Could not get data from DBs. Elastic, nor Mysql is working! Check DB status.');
                }
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
}
