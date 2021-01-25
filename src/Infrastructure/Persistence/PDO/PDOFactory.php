<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use InvalidArgumentException;
use PDO;

class PDOFactory
{
    private const MYSQL_DRIVER = 'mysql';
    private const SQLITE_DRIVER = 'sqlite';

    public static function createFromDsn(string $dsn): PDO
    {
        if (self::MYSQL_DRIVER === parse_url($dsn, PHP_URL_SCHEME)) {
            return new PDO(
                sprintf(
                    '%s:host=%s;dbname=%s',
                    parse_url($dsn, PHP_URL_SCHEME),
                    parse_url($dsn, PHP_URL_HOST),
                    ltrim(parse_url($dsn, PHP_URL_PATH), "/")
                ),
                parse_url($dsn, PHP_URL_USER),
                parse_url($dsn, PHP_URL_PASS)
            );
        }

        if (self::SQLITE_DRIVER === parse_url($dsn, PHP_URL_SCHEME)) {
            return new PDO($dsn);
        }

        throw new InvalidArgumentException('Database driver not supported.');
    }
}
