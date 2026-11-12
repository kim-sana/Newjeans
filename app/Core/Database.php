<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) config('database.host', '127.0.0.1');
        $port = (int) config('database.port', 3306);
        $database = (string) config('database.database', 'newjeans');
        $charset = (string) config('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) config('database.username', 'root'),
                (string) config('database.password', ''),
                (array) config('database.options', [])
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database connection failed: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }

        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
