<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Akses database tunggal (PDO). Koneksi dibuka saat query pertama,
 * jadi halaman yang seluruh datanya dari cache tidak membuka koneksi.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $c = Config::get('database');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);

        try {
            $pdo = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // Dibungkus ulang agar argumen konstruktor (termasuk password) tidak ikut ke stack trace
            throw new RuntimeException('Koneksi database gagal: ' . $e->getMessage(), (int) $e->getCode());
        }

        if (!empty($c['timezone'])) {
            $pdo->exec('SET time_zone = ' . $pdo->quote($c['timezone']));
        }

        return self::$pdo = $pdo;
    }

    /** @return array<string, mixed>|null */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** INSERT/UPDATE/DELETE — mengembalikan jumlah baris terdampak. */
    public static function execute(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function run(string $sql, array $params): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(
                is_int($key) ? $key + 1 : ':' . ltrim($key, ':'),
                $value,
                match (true) {
                    is_int($value)  => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    $value === null => PDO::PARAM_NULL,
                    default         => PDO::PARAM_STR,
                }
            );
        }
        $stmt->execute();

        return $stmt;
    }
}
