<?php

namespace App\Drivers\SQL;

interface IMySQLDriver
{
    public function findProduct(string $id): array;
}
