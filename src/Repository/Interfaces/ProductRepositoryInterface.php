<?php

namespace App\Repository\Interfaces;

use JetBrains\PhpStorm\ArrayShape;

interface ProductRepositoryInterface
{
    #[ArrayShape(['saved' => "bool", 'product.content' => "array|null"])]
    public function findById(string $id): array;

    #[ArrayShape(['saved' => "bool", 'product.content' => "string"])]
    public function insertProduct(array $content): array;
}