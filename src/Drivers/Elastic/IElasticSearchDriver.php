<?php

namespace App\Drivers\Elastic;

interface IElasticSearchDriver
{
    public function findById(string $id): array;
}
