<?php

namespace App\Drivers\SQL;

use App\Exceptions\MySQLOutOffOrderException;

/**
 * Class MySQLDriver
 * Description: dummy sql driver, this is just mere example.
 */
class MySQLDriver implements IMySQLDriver
{
    /**
     * @return string[][]
     *
     * @throws MySQLOutOffOrderException
     */
    public function findProduct(string $id): array
    {
        if (2 === intval($id)) {
            return [
                ['MysqlSource', 'value1'],
                ['key2', 'value2'],
                ['key3', 'value3'],
            ];
        } else {
            throw new MySQLOutOffOrderException();
        }
    }
}
