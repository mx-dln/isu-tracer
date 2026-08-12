<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * PDO connection manager (singleton).
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $db = config('database');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );

        try {
            $pdo = new PDO($dsn, $db['username'], $db['password'], $db['options']);
            $pdo->exec(sprintf('SET NAMES %s COLLATE %s', $db['charset'], $db['collation']));
            $pdo->exec('SET time_zone = "+08:00"');
            self::$instance = $pdo;
            return $pdo;
        } catch (\PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Unable to connect to the database. Please check your configuration.', 500, $e);
        }
    }

    /**
     * Run a query with parameters and return the prepared statement.
     */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row.
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Begin a transaction.
     */
    public static function beginTransaction(): void
    {
        self::connect()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connect()->commit();
    }

    public static function rollBack(): void
    {
        self::connect()->rollBack();
    }

    /**
     * Execute a callable inside a transaction; rolls back on exception.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return self::connect()->lastInsertId();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
