<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Services\ProductService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
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

    /**
     * @var array
     */
    private $indexDefinition;

    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(
        ProductRepository $productRepository,
        TagAwareCacheInterface $cache,
        LoggerInterface $logger,
        ProductService $productService,
        $indexDefinition
    ) {
        $this->productRepository = $productRepository;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->indexDefinition = $indexDefinition;
        $this->productService = $productService;
    }

    /**
     * @Route("/product/detail/{id}", name="detail_product")
     *
     * @throws InvalidArgumentException
     */
    public function detail(string $id): JsonResponse
    {
        $response['data'] = $this->productService->handleDBConnections($id, $this->indexDefinition);
        if ($response) {
            $response['count'] = $this->productService->updateProductCounter($id);
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/product/create/{content}", name="create_product")
     */
    public function create(string $content): JsonResponse
    {
        //TODO: never ever do this, you should clean content before inserting to DB, but this is only example.
        $content = ['data' => $content];
        $productArray = $this->productRepository->insertProduct($content);

        return new JsonResponse($productArray);
    }
}
