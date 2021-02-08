<?php

namespace App\Controller;

use App\Repository\MysqlProductRepository;
use App\Services\ProductService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    public function __construct(
        protected MysqlProductRepository $productRepository,
        protected TagAwareCacheInterface $cache,
        protected LoggerInterface $logger,
        protected ProductService $productService
    ) {}

    /**
     * @Route("/product/detail/{id}", name="detail_product")
     *
     * @param string $id
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function detail(string $id): JsonResponse
    {
        $response['data'] = $this->productService->handleDBConnections($id);
        if ($response) {
            $response['count'] = $this->productService->updateProductCounter($id);
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/product/create/{content}", name="create_product")
     * @param string $content
     * @return JsonResponse
     */
    public function create(string $content): JsonResponse
    {
        //TODO: never ever do this, you should clean content before inserting to DB, but this is only example.
        $content = ['data' => $content];
        $productArray = $this->productService->handleSaveProduct($content);

        return new JsonResponse($productArray);
    }
}
