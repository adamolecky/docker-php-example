<?php

namespace App\Drivers\Elastic;

use App\Exceptions\ElasticOutOffOrderException;

/**
 * Class ElasticSearchDriver
 * Description: dummy sql driver, this is just mere example.
 */
class ElasticSearchDriver implements IElasticSearchDriver
{
    /**
     * @return array|void
     *
     * @throws ElasticOutOffOrderException
     */
    public function findById(string $id): array
    {
        if (1 === intval($id)) {
            return [
                ['ElasticSource', 'value1'],
                ['key2', 'value2'],
                ['key3', 'value3'],
            ];
        } else {
            throw new ElasticOutOffOrderException();
        }
    }
}
